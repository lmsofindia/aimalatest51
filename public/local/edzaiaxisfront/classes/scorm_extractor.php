<?php
/**
 * SCORM content extractor — reads a SCORM package and returns structured chunks.
 *
 * Supports three SCORM authoring tool formats:
 *  - scorm360  : Articulate Storyline 360 / Studio 360
 *  - rise      : Articulate Rise 360 (full course_data.js)
 *  - rise_simple: Rise 360 without course_data.js (HTML scraping fallback)
 *  - generic   : Any SCORM 1.2 / SCORM 2004 (text extraction from story_content/*.html)
 *
 * Returns an array of structured chunks matching the StructuredChunk schema:
 * [
 *   'sequence'         => int,
 *   'chunk_type'       => string (slide|scene|lesson|section|page),
 *   'title'            => string|null,
 *   'text'             => string,
 *   'has_audio'        => bool,
 *   'audio_transcript' => string|null,
 *   'embed_url'        => string|null,
 * ]
 *
 * @package    local_edzaiaxisfront
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edzaiaxisfront;

defined('MOODLE_INTERNAL') || die();

class scorm_extractor {

    private \cm_info $cm;
    private string   $scorm_path;    // Filesystem path to unpacked SCORM
    private string   $format;        // Detected format

    public function __construct(\cm_info $cm) {
        $this->cm = $cm;
        $this->scorm_path = $this->resolve_scorm_path();
        $this->format     = $this->detect_format();
    }

    /**
     * Extract all content chunks from the SCORM package.
     *
     * @return array  List of chunk arrays (see class docblock)
     */
    public function extract(): array {
        return match ($this->format) {
            'scorm360'   => $this->extract_storyline(),
            'rise'       => $this->extract_rise(),
            'rise_simple'=> $this->extract_rise_simple(),
            default      => $this->extract_generic(),
        };
    }

    /**
     * Detect the authoring tool format by inspecting key files.
     */
    public function detect_format(): string {
        // Articulate Rise: has story_content/course_data.js
        if (file_exists($this->scorm_path . '/story_content/course_data.js')) {
            return 'rise';
        }
        // Articulate Rise (simple / export variant): no course_data.js but has index_lms.html referencing rise
        if (file_exists($this->scorm_path . '/index_lms.html')) {
            $idx = file_get_contents($this->scorm_path . '/index_lms.html');
            if ($idx && stripos($idx, 'rise') !== false) {
                return 'rise_simple';
            }
        }
        // Articulate Storyline 360: has story.html and story_content folder
        if (file_exists($this->scorm_path . '/story.html') &&
            is_dir($this->scorm_path . '/story_content')) {
            return 'scorm360';
        }
        return 'generic';
    }

    // ── Storyline 360 ────────────────────────────────────────────────────────

    private function extract_storyline(): array {
        $chunks   = [];
        $seq      = 1;
        $html_dir = $this->scorm_path . '/story_content';

        if (!is_dir($html_dir)) return $this->extract_generic();

        // Collect all slide HTML files, sorted numerically
        $files = glob($html_dir . '/slide*.html');
        if (!$files) $files = glob($html_dir . '/*.html');
        if (!$files) return $this->extract_generic();

        natsort($files);

        foreach ($files as $file) {
            $basename = basename($file);
            // Skip assets/template files
            if (preg_match('/^(frame|shell|meta|lib|player|data)/', $basename)) continue;

            $html  = file_get_contents($file);
            if (!$html) continue;

            $title = $this->extract_html_title($html)
                  ?: $this->extract_og_title($html)
                  ?: preg_replace('/\.(html?)$/i', '', $basename);

            $text  = $this->html_to_text($html);
            if (strlen(trim($text)) < 20) continue;  // Skip near-empty slides

            $has_audio    = $this->detect_audio($html);
            $transcript   = $this->extract_transcript($html);

            $chunks[] = [
                'sequence'         => $seq++,
                'chunk_type'       => 'slide',
                'title'            => $title,
                'text'             => $text,
                'has_audio'        => $has_audio,
                'audio_transcript' => $transcript,
                'embed_url'        => null,
            ];
        }

        return $chunks ?: $this->extract_generic();
    }

    // ── Rise 360 (with course_data.js) ───────────────────────────────────────

    private function extract_rise(): array {
        $js_path = $this->scorm_path . '/story_content/course_data.js';
        $js      = file_get_contents($js_path);
        if (!$js) return $this->extract_rise_simple();

        // Strip JS wrapper to get raw JSON: "window.courseData = {...};" or similar
        $json = preg_replace('/^[\w\s\.]*=\s*/', '', trim($js));
        $json = rtrim($json, ';');

        $data = json_decode($json, true);
        if (!$data) return $this->extract_rise_simple();

        $chunks = [];
        $seq    = 1;

        $lessons = $data['lessons'] ?? $data['blocks'] ?? [];
        foreach ($lessons as $lesson) {
            $title  = $lesson['title'] ?? null;
            $blocks = $lesson['blocks'] ?? $lesson['items'] ?? [$lesson];
            $texts  = [];

            foreach ($blocks as $block) {
                $type = $block['type'] ?? '';
                if (in_array($type, ['text', 'paragraph', 'heading', 'list', 'quote'])) {
                    $raw = $block['body'] ?? $block['text'] ?? $block['html'] ?? '';
                    if ($raw) $texts[] = $this->html_to_text($raw);
                }
                if ($type === 'video' && !empty($block['url'])) {
                    $chunks[] = [
                        'sequence'         => $seq++,
                        'chunk_type'       => 'lesson',
                        'title'            => $block['title'] ?? $title,
                        'text'             => $block['caption'] ?? $block['description'] ?? '',
                        'has_audio'        => true,
                        'audio_transcript' => null,
                        'embed_url'        => $block['url'],
                    ];
                }
            }

            $combined = implode("\n\n", array_filter($texts));
            if (strlen(trim($combined)) >= 20) {
                $chunks[] = [
                    'sequence'         => $seq++,
                    'chunk_type'       => 'lesson',
                    'title'            => $title,
                    'text'             => $combined,
                    'has_audio'        => false,
                    'audio_transcript' => null,
                    'embed_url'        => null,
                ];
            }
        }

        return $chunks ?: $this->extract_rise_simple();
    }

    // ── Rise simple (HTML scraping fallback) ─────────────────────────────────

    private function extract_rise_simple(): array {
        $chunks = [];
        $seq    = 1;

        // Rise exports a single index.html or lesson HTML files
        $html_files = array_filter([
            $this->scorm_path . '/index.html',
            $this->scorm_path . '/index_lms.html',
        ], 'file_exists');

        // Also look for per-lesson HTML
        $lesson_files = glob($this->scorm_path . '/lessons/*.html') ?: [];
        $all = array_merge(array_values($html_files), $lesson_files);

        foreach ($all as $file) {
            $html  = file_get_contents($file);
            if (!$html) continue;

            $title = $this->extract_html_title($html) ?: basename($file);
            $text  = $this->html_to_text($html);
            if (strlen(trim($text)) < 20) continue;

            // Split large files by H2/H3 headings
            $sections = $this->split_by_headings($html);
            if (count($sections) > 1) {
                foreach ($sections as $section) {
                    $stitle = $section['title'] ?? $title;
                    $stext  = $section['text'];
                    if (strlen(trim($stext)) < 20) continue;
                    $chunks[] = [
                        'sequence'         => $seq++,
                        'chunk_type'       => 'section',
                        'title'            => $stitle,
                        'text'             => $stext,
                        'has_audio'        => false,
                        'audio_transcript' => null,
                        'embed_url'        => null,
                    ];
                }
            } else {
                $chunks[] = [
                    'sequence'         => $seq++,
                    'chunk_type'       => 'page',
                    'title'            => $title,
                    'text'             => $text,
                    'has_audio'        => $this->detect_audio($html),
                    'audio_transcript' => null,
                    'embed_url'        => null,
                ];
            }
        }

        return $chunks ?: $this->extract_generic();
    }

    // ── Generic SCORM extractor ───────────────────────────────────────────────

    private function extract_generic(): array {
        $chunks     = [];
        $seq        = 1;
        $html_files = $this->find_html_files();

        foreach ($html_files as $file) {
            $html  = file_get_contents($file);
            if (!$html) continue;

            $title = $this->extract_html_title($html) ?: basename($file, '.html');
            $text  = $this->html_to_text($html);
            if (strlen(trim($text)) < 20) continue;

            $chunks[] = [
                'sequence'         => $seq++,
                'chunk_type'       => 'page',
                'title'            => $title,
                'text'             => $text,
                'has_audio'        => $this->detect_audio($html),
                'audio_transcript' => null,
                'embed_url'        => null,
            ];
        }

        return $chunks;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Resolve the filesystem path of the unpacked SCORM for this CM.
     */
    private function resolve_scorm_path(): string {
        global $CFG, $DB;

        $scorm = $DB->get_record('scorm', ['id' => $this->cm->instance], '*', MUST_EXIST);
        $dataroot = $CFG->dataroot;

        // Moodle stores unpacked SCORM packages under dataroot/scorm/<scormid>/
        // or within the context's file area depending on version
        $path = $dataroot . '/scorm/' . $scorm->id;
        if (is_dir($path)) return $path;

        // Fallback: look in the context file area (stored as a zip)
        $context = \context_module::instance($this->cm->id);
        $fs      = get_file_storage();
        $files   = $fs->get_area_files($context->id, 'mod_scorm', 'package', false, 'id DESC', false, 0, 0, 1);
        if (!$files) return '';

        $file    = reset($files);
        $tmpdir  = make_request_directory();
        $file->extract_to_pathname(get_file_packer('application/zip'), $tmpdir);

        return $tmpdir;
    }

    private function find_html_files(): array {
        if (!$this->scorm_path || !is_dir($this->scorm_path)) return [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->scorm_path, \FilesystemIterator::SKIP_DOTS)
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && in_array(strtolower($file->getExtension()), ['html', 'htm'])) {
                $name = strtolower($file->getBasename());
                // Skip framework/player files
                if (preg_match('/^(frame|shell|meta|lib|player|tincan|cmi|lms|api)/', $name)) continue;
                $files[] = $file->getPathname();
            }
        }

        natsort($files);
        return array_values($files);
    }

    private function extract_html_title(string $html): string {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
            return trim(html_entity_decode(strip_tags($m[1])));
        }
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $m)) {
            return trim(html_entity_decode(strip_tags($m[1])));
        }
        return '';
    }

    private function extract_og_title(string $html): string {
        if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)/i', $html, $m)) {
            return trim($m[1]);
        }
        return '';
    }

    private function html_to_text(string $html): string {
        // Remove scripts and styles first
        $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
        // Preserve line breaks for block elements
        $html = preg_replace('/<\/(p|div|li|h[1-6]|br|tr)[^>]*>/i', "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Normalise whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        return trim($text);
    }

    private function detect_audio(string $html): bool {
        return (bool) preg_match('/<(audio|source)[^>]+(\.mp3|\.ogg|\.wav|\.m4a)/i', $html);
    }

    private function extract_transcript(string $html): ?string {
        // Storyline 360 can include a hidden transcript div
        if (preg_match('/<div[^>]+class=["\'][^"\']*transcript[^"\']*["\'][^>]*>(.*?)<\/div>/is', $html, $m)) {
            $t = $this->html_to_text($m[1]);
            return strlen(trim($t)) > 10 ? $t : null;
        }
        return null;
    }

    private function split_by_headings(string $html): array {
        // Split on H2 or H3 headings to create sub-sections
        $parts   = preg_split('/(<h[23][^>]*>)/i', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        $sections = [];
        $current_title = null;
        $current_html  = '';

        for ($i = 0; $i < count($parts); $i++) {
            if (preg_match('/^<h[23]/i', $parts[$i])) {
                if ($current_html) {
                    $sections[] = ['title' => $current_title, 'text' => $this->html_to_text($current_html)];
                }
                // Next part is the heading text + rest
                $i++;
                $heading_block = ($parts[$i] ?? '');
                if (preg_match('/>(.*?)<\/h[23]>/is', $heading_block, $m)) {
                    $current_title = strip_tags($m[1]);
                }
                $current_html = $heading_block;
            } else {
                $current_html .= $parts[$i];
            }
        }
        if ($current_html) {
            $sections[] = ['title' => $current_title, 'text' => $this->html_to_text($current_html)];
        }

        return $sections;
    }
}
