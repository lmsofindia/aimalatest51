<?php

/**
 * Content router — detects Moodle activity type and builds the correct
 * ingest payload for axis-ai.
 *
 * Supported types:
 *   mod_resource  → PDF / video / other file (by MIME type)
 *   mod_url       → YouTube / Vimeo / PeerTube / generic URL
 *   mod_scorm     → SCORM package (extracted by PHP, sent as structured chunks)
 *   mod_page      → Moodle Page (HTML extracted)
 *   mod_book      → Moodle Book (chapters extracted)
 *
 * Unsupported: mod_forum, mod_assign, mod_quiz, mod_lesson (for now)
 *
 * @package    local_edzaiaxisfront
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edzaiaxisfront;

defined('MOODLE_INTERNAL') || die();

class content_router
{

    /** @var array Modules this router supports */
    // NOTE: 'hvp' (H5P) is intentionally NOT listed. detect_content_type() has no
    // hvp branch yet, so H5P would fall through to 'unsupported' and ingest would
    // silently no-op. Re-add 'hvp' here only once an H5P extraction path exists
    // (structured ingest, like SCORM). Until then it must not appear ingestable.
    const SUPPORTED_MODULES = ['resource', 'url', 'scorm', 'page', 'book', 'videotime', 'edztrackvideo', 'pdfprotect'];

    /** @var array MIME types treated as PDF */
    const PDF_MIMES = ['application/pdf'];

    /** @var array MIME types treated as video (sent to video pipeline) */
    const VIDEO_MIMES = [
        'video/mp4',
        'video/webm',
        'video/ogg',
        'video/quicktime',
        'application/octet-stream',  // some .mp4 uploads
    ];

    /** @var array YouTube URL patterns */
    const YOUTUBE_PATTERNS = [
        'youtube.com/watch',
        'youtu.be/',
        'youtube.com/embed/',
    ];

    /** @var array Vimeo URL patterns */
    const VIMEO_PATTERNS = [
        'vimeo.com/',
        'player.vimeo.com/',
    ];

    /**
     * Check if a course module is supported by this plugin.
     *
     * @param  \cm_info  $cm
     * @return bool
     */
    public static function is_supported(\cm_info $cm): bool
    {
        return in_array($cm->modname, self::SUPPORTED_MODULES);
    }

    /**
     * Detect the content type string for a CM.
     * Returns one of: pdf|youtube|vimeo|peertube|scorm|page|book|video|unsupported
     *
     * @param  \cm_info  $cm
     * @return string
     */
    public static function detect_content_type(\cm_info $cm): string
    {
        global $DB;
        switch ($cm->modname) {
            case 'scorm':
                return 'scorm';
            case 'page':
                return 'html_page'; // in python we are using html_page for Moodle Pages to distinguish from generic URLs that happen to point to HTML
            case 'book':
                return 'book';
                // case 'url':
                //     return self::detect_url_type($cm->externalurl ?? ''); // this is not url but externalurl field from url module
                // for mod_url we need to fetch the external URL from the database to detect if it's YouTube/Vimeo/PeerTube or generic URL
            case 'url':
                if (empty($cm->instance)) {
                    return 'unknown';
                }
                // Fetch URL record
                $urlrec = $DB->get_record('url', ['id' => $cm->instance], 'externalurl', IGNORE_MISSING);
                if (!$urlrec || empty($urlrec->externalurl)) {
                    return 'unknown';
                }
                return self::detect_url_type($urlrec->externalurl);
            case 'videotime':
                return 'videotime'; // url will be vimeo_url from videotime module
            case 'edztrackvideo':
                // Custom mod: sourcetype = youtube | vimeo | upload.
                $etv = $DB->get_record('edztrackvideo', ['id' => $cm->instance], '*', IGNORE_MISSING);
                if (!$etv) {
                    return 'unknown';
                }
                if ($etv->sourcetype === 'upload') {
                    return 'video_upload';                 // local file → Whisper (VideoUploadExtractor)
                }
                if ($etv->sourcetype === 'youtube') {
                    return 'youtube';
                }
                if ($etv->sourcetype === 'vimeo') {
                    return 'vimeo';
                }
                // Unknown sourcetype — fall back to sniffing the external URL.
                return !empty($etv->externalurl) ? self::detect_url_type($etv->externalurl) : 'unknown';
            case 'pdfprotect':
                // pdfprotect = a DRM-wrapped PDF; the file is always a PDF.
                return 'pdf';
            case 'resource':
                return self::detect_resource_type($cm);
            default:
                return 'unsupported';
        }
    }

    /**
     * Build the ingest payload for axis-ai.
     * Returns null if the CM is unsupported.
     *
     * @param  \cm_info  $cm
     * @param  int       $moodle_user_id  User who triggered the ingest
     * @param  array     $options         Additional options (tasks, language, etc.)
     * @return array|null
     */
    public static function build_ingest_payload(\cm_info $cm, int $moodle_user_id, array $options = []): ?array
    {
        $content_type = self::detect_content_type($cm);

        //Mihir - find the token
        // 🔥 STEP C: append forcedownload + token
        $moodlewebservice_token = get_config('local_edzaiaxisfront', 'apitoken');

        if ($content_type === 'unsupported') {
            return null;
        }

        $base = [
            'moodle_course_id' => (int) $cm->course,
            'moodle_cmid'      => (int) $cm->id,
            'moodle_user_id'   => $moodle_user_id,
            'moodle_section_id' => (int) ($cm->sectionnum ?? 0),
            'title'            => $cm->name,
            'content_type'     => $content_type,
            'options'          => [
                'tasks'              => $options['tasks'] ?? ['summary'],
                'language'           => $options['language'] ?? '',
                'output_language'    => $options['output_language'] ?? '',
                'chunk_size'         => $options['chunk_size'] ?? 1000,
                'chunk_overlap'      => $options['chunk_overlap'] ?? 200,
                'chunking_strategy'  => $options['chunking_strategy'] ?? 'recursive',
            ],
            'metadata' => [],
        ];

        switch ($content_type) {
            case 'scorm':
                // SCORM goes through /ingest/structured after PHP extraction
                // This method returns null — caller uses build_scorm_structured_payload() instead
                return null;

            case 'html_page':
                // mod_page: send the full HTML body so MoodlePageExtractor in axis-ai can
                // parse it (BeautifulSoup) and detect embedded YouTube/Vimeo <iframe> elements.
                // content_type stays 'html_page' to match ContentType.HTML_PAGE enum.
                $base['source_url']     = (string) new \moodle_url('/mod/page/view.php', ['id' => $cm->id]);
                $base['metadata']['html_content'] = self::extract_page_content($cm);
                // A privately-hosted Vimeo embedded in the page needs the API token for
                // axis-ai's page extractor to fetch it (public embeds don't). The page
                // extractor reads metadata['vimeo_token'] when transcribing embeds.
                $base['metadata']['vimeo_token'] = get_config('local_edzaiaxisfront', 'vimeotoken');
                break;

            case 'book':
                $base['source_url']     = (string) new \moodle_url('/mod/book/view.php', ['id' => $cm->id]);
                $base['metadata']['chapters'] = self::extract_book_chapters($cm);
                break;

            case 'pdf':
            case 'video_upload':
                // edztrackvideo uploads live in the mod_edztrackvideo/videofile area,
                // not mod_resource/content.
                if ($cm->modname === 'edztrackvideo') {
                    $file_url = self::get_edztrackvideo_file_url($cm);
                } else if ($cm->modname === 'pdfprotect') {
                    $file_url = self::get_pdfprotect_url($cm);
                } else {
                    $file_url = self::get_resource_url($cm);
                }
                if (!$file_url) return null;

                // $base['source_url'] = $file_url;

                // for resource and video types we have to create the pluginfile along with webservice and token
                // =======================
                // ✅ FIX 2: Transform source_url → webservice/pluginfile
                // =======================
                if (!empty($file_url)) {
                    $url = $file_url;
                    // 🔥 A: Convert to webservice pluginfile
                    $url = str_replace(
                        '/pluginfile.php/',
                        '/webservice/pluginfile.php/',
                        $url
                    );

                    if (!empty($moodlewebservice_token)) {
                        $url .= (strpos($url, '?') === false ? '?' : '&') .
                            'forcedownload=1&token=' . $moodlewebservice_token;
                    } else {
                        throw new \moodle_exception('Missing API token for pluginfile access');
                    }
                    // ✅ Assign back
                    $base['source_url'] = $url;
                }
                break;

            case 'youtube':
            case 'peertube':
                // edztrackvideo stores the link in its own table, not the mod_url table.
                $base['source_url'] = ($cm->modname === 'edztrackvideo')
                    ? self::get_edztrackvideo_external_url($cm)
                    : self::get_url_module_url($cm);
                break;
            case 'vimeo':
                $base['source_url'] = ($cm->modname === 'edztrackvideo')
                    ? self::get_edztrackvideo_external_url($cm)
                    : self::get_url_module_url($cm);
                // we need to send the Vimeo token for axis-ai to fetch the video content, since Vimeo URLs often require authenticated API calls to get the actual video file URL
                $base['metadata']['vimeo_token']  = get_config('local_edzaiaxisfront', 'vimeotoken'); // Mihir - fetch vimeo token from config
                break;
            case 'videotime':
                $base['source_url'] = self::get_videotime_module_url($cm);
                $base['content_type'] = 'vimeo'; // treat as vimeo for axis-ai since it's a Vimeo URL, even though module is videotime
                break;

            case 'url':
                // Generic web page from mod_url — fetch the URL and extract via html_page extractor in axis-ai.
                // detect_url_type() returned 'url' meaning it was not YouTube/Vimeo/PeerTube.
                $generic_url = self::get_url_module_url($cm);
                if (empty($generic_url)) return null;
                $base['source_url']   = $generic_url;
                $base['content_type'] = 'html_page'; // Python schema: html_page = web page extractor
                break;

            case 'unknown':
            case 'unsupported':
            default:
                return null;
        }

        return $base;
    }

    /**
     * Build the structured ingest payload for SCORM content.
     * PHP extracts the SCORM content; this method packages it for /ingest/structured.
     *
     * @param  \cm_info  $cm
     * @param  array     $chunks   Pre-extracted chunks from SCORM extractor
     * @param  int       $moodle_user_id
     * @param  array     $options
     * @return array
     */
    public static function build_scorm_structured_payload(\cm_info $cm, array $chunks, int $moodle_user_id, array $options = []): array
    {
        return [
            'moodle_course_id'  => (int) $cm->course,
            'moodle_cmid'       => (int) $cm->id,
            'moodle_user_id'    => $moodle_user_id,
            'moodle_section_id' => (int) ($cm->sectionnum ?? 0),
            'title'             => $cm->name,
            'content_type'      => 'scorm',
            'language'          => $options['language'] ?? '',
            'output_language'   => $options['output_language'] ?? '',
            'chunks'            => $chunks,
            'options'           => [
                'tasks'             => $options['tasks'] ?? ['summary'],
                'chunk_size'        => $options['chunk_size'] ?? 1000,
                'chunk_overlap'     => $options['chunk_overlap'] ?? 200,
                'chunking_strategy' => 'recursive',
            ],
            'metadata'          => ['source' => 'scorm_php_extractor'],
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private static function detect_url_type(string $url): string
    {
        foreach (self::YOUTUBE_PATTERNS as $pattern) {
            if (strpos($url, $pattern) !== false) return 'youtube';
        }
        foreach (self::VIMEO_PATTERNS as $pattern) {
            if (strpos($url, $pattern) !== false) return 'vimeo';
        }
        // PeerTube detection: /w/ or /videos/watch/ in URL path
        if (preg_match('#/(?:w/[a-zA-Z0-9]{15,}|videos/watch/)#', $url)) {
            return 'peertube';
        }
        return 'url';  // Generic URL — axis-ai will try to fetch as web page
    }

    private static function detect_resource_type(\cm_info $cm): string
    {
        global $DB;

        // Get the file associated with this resource
        $context = \context_module::instance($cm->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_resource', 'content', false, 'sortorder', false);

        foreach ($files as $file) {
            $mime = $file->get_mimetype();
            if (in_array($mime, self::PDF_MIMES)) return 'pdf';
            // axis-ai's ContentType enum value is 'video_upload' (VideoUploadExtractor =
            // Whisper transcription). Sending 'video' is rejected as an unsupported type.
            if (in_array($mime, self::VIDEO_MIMES)) return 'video_upload';
            // PowerPoint / Word — treated as documents, use PDF pipeline
            if (in_array($mime, [
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ])) {
                return 'pdf';  // axis-ai PDF extractor handles these too
            }
        }
        return 'unsupported';
    }

    private static function get_resource_url(\cm_info $cm): ?string
    {
        $context = \context_module::instance($cm->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_resource', 'content', false, 'sortorder', false);

        foreach ($files as $file) {
            if ($file->get_filename() === '.') continue;
            return \moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename(),
                false
            )->out(false);
        }
        return null;
    }

    /**
     * Resolve the PDF inside a mod_pdfprotect instance. The PDF is DRM-wrapped for the
     * front-end pdf.js viewer but still lives in Moodle file storage, so a token
     * webservice/pluginfile URL can fetch it. The filearea varies by pdfprotect version,
     * so we scan the files table for this module context and pick the first PDF.
     */
    private static function get_pdfprotect_url(\cm_info $cm): ?string
    {
        global $DB;
        $context = \context_module::instance($cm->id);
        $rows = $DB->get_records_select(
            'files',
            "contextid = :ctx AND component = 'mod_pdfprotect' AND filename <> '.' AND filesize > 0",
            ['ctx' => $context->id],
            'filearea ASC, sortorder ASC, id ASC',
            'id, filearea, itemid, filepath, filename, mimetype'
        );
        $pick = null;
        foreach ($rows as $r) {
            if (in_array($r->mimetype, self::PDF_MIMES) || preg_match('/\\.pdf$/i', $r->filename)) {
                $pick = $r;
                break;
            }
            if ($pick === null) {
                $pick = $r;
            }
        }
        if ($pick === null) {
            return null;
        }
        return \moodle_url::make_pluginfile_url(
            $context->id, 'mod_pdfprotect', $pick->filearea, $pick->itemid,
            $pick->filepath, $pick->filename, false
        )->out(false);
    }

    /**
     * True when this module's file cannot be fetched by axis-ai over webservice/pluginfile
     * (e.g. mod_pdfprotect DRM) and must instead be uploaded as raw bytes.
     */
    public static function needs_file_upload(\cm_info $cm): bool
    {
        return $cm->modname === 'pdfprotect';
    }

    /**
     * The stored PDF file inside a mod_pdfprotect instance (for byte-level upload).
     */
    public static function get_pdfprotect_file(\cm_info $cm): ?\stored_file
    {
        global $DB;
        $context = \context_module::instance($cm->id);
        $rows = $DB->get_records_select(
            'files',
            "contextid = :ctx AND component = 'mod_pdfprotect' AND filename <> '.' AND filesize > 0",
            ['ctx' => $context->id],
            'filearea ASC, sortorder ASC, id ASC',
            'id, filearea, itemid, filepath, filename, mimetype'
        );
        $pick = null;
        foreach ($rows as $r) {
            if (in_array($r->mimetype, self::PDF_MIMES) || preg_match('/\\.pdf$/i', $r->filename)) {
                $pick = $r;
                break;
            }
            if ($pick === null) {
                $pick = $r;
            }
        }
        if ($pick === null) {
            return null;
        }
        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'mod_pdfprotect', $pick->filearea, $pick->itemid, $pick->filepath, $pick->filename);
        return $file ?: null;
    }

    private static function get_url_module_url(\cm_info $cm): string
    {
        global $DB;
        $url_record = $DB->get_record('url', ['id' => $cm->instance], 'externalurl');
        return $url_record ? $url_record->externalurl : '';
    }

    /**
     * External URL (YouTube/Vimeo) stored on an edztrackvideo instance.
     */
    private static function get_edztrackvideo_external_url(\cm_info $cm): string
    {
        global $DB;
        $rec = $DB->get_record('edztrackvideo', ['id' => $cm->instance], 'externalurl', IGNORE_MISSING);
        return $rec ? (string) $rec->externalurl : '';
    }

    /**
     * Raw pluginfile URL of an uploaded edztrackvideo video
     * (component mod_edztrackvideo, filearea 'videofile', itemid 0).
     * The caller rewrites this to a tokenised /webservice/pluginfile.php URL.
     */
    private static function get_edztrackvideo_file_url(\cm_info $cm): ?string
    {
        $context = \context_module::instance($cm->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_edztrackvideo', 'videofile', false, 'sortorder', false);

        foreach ($files as $file) {
            if ($file->get_filename() === '.') {
                continue;
            }
            return \moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename(),
                false
            )->out(false);
        }
        return null;
    }

    /* Mihir for videotime */
    private static function get_videotime_module_url(\cm_info $cm): string
    {
        global $DB;
        $url_record = $DB->get_record('videotime', ['id' => $cm->instance], 'vimeo_url');
        return $url_record ? $url_record->vimeo_url : '';
    }

    private static function extract_page_content(\cm_info $cm): string
    {
        global $DB;
        $page = $DB->get_record('page', ['id' => $cm->instance], 'content,contentformat');
        if (!$page) return '';
        // Send the processed HTML — NOT strip_tags'd — so the Python MoodlePageExtractor
        // can: (a) parse it with BeautifulSoup to extract clean text, and
        //       (b) detect embedded YouTube/Vimeo <iframe> elements for transcript extraction.
        // strip_tags() was previously used here but it silently discards <iframe> embeds,
        // breaking video transcript extraction for pages with embedded videos.
        return format_text($page->content, $page->contentformat);
    }

    private static function extract_book_chapters(\cm_info $cm): array
    {
        global $DB;
        $chapters = $DB->get_records(
            'book_chapters',
            ['bookid' => $cm->instance, 'hidden' => 0],
            'pagenum ASC',
            'id,title,content,contentformat'
        );
        $result = [];
        foreach ($chapters as $ch) {
            $result[] = [
                'title'   => $ch->title,
                'content' => strip_tags(format_text($ch->content, $ch->contentformat)),
            ];
        }
        return $result;
    }
}
