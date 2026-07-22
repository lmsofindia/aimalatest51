<?php
/**
 * Builds the per-source player config consumed by the JS core.
 *
 * @package   mod_edztrackvideo
 */

namespace mod_edztrackvideo;

defined('MOODLE_INTERNAL') || die();

class source_resolver {

    /**
     * Build the JS player config for an instance.
     *
     * @param \stdClass $ed edztrackvideo record
     * @param \stdClass|\cm_info $cm
     * @param \context_module $context
     * @return array
     */
    public static function build_player_config($ed, $cm, $context): array {
        $config = [
            'edztrackvideoid' => (int)$ed->id,
            'cmid' => (int)$cm->id,
            'sourcetype' => (string)$ed->sourcetype,
            'no_forward_seek_first_view' => (bool)$ed->no_forward_seek_first_view,
            'completion_threshold' => (int)$ed->completion_threshold,
            'allow_speed' => (bool)$ed->allow_speed,
            'max_speed' => (float)$ed->max_speed,
            'videoid' => '',
            'videohash' => '',
            'fileurl' => '',
            'mimetype' => '',
        ];

        switch ($ed->sourcetype) {
            case 'youtube':
                $config['videoid'] = (string)$ed->videoid;
                break;

            case 'vimeo':
                $config['videoid'] = (string)$ed->videoid;
                $config['videohash'] = (string)$ed->videohash;
                break;

            case 'upload':
                $file = self::get_uploaded_file($context);
                if ($file) {
                    $url = \moodle_url::make_pluginfile_url(
                        $context->id,
                        'mod_edztrackvideo',
                        MOD_EDZTRACKVIDEO_FILEAREA,
                        0,
                        $file->get_filepath(),
                        $file->get_filename()
                    );
                    $config['fileurl'] = $url->out(false);
                    $config['mimetype'] = $file->get_mimetype();
                }
                break;

            case 'serverfile':
                // Streamed through serve.php (access-controlled + range).
                $url = new \moodle_url('/mod/edztrackvideo/serve.php', ['id' => $cm->id]);
                $config['fileurl'] = $url->out(false);
                $config['mimetype'] = self::guess_mimetype((string)$ed->serverpath);
                break;

            case 'directurl':
                $config['fileurl'] = (string)$ed->externalurl;
                $config['mimetype'] = self::guess_mimetype((string)$ed->externalurl);
                break;
        }

        return $config;
    }

    /**
     * Guess a video mimetype from a path or URL (query string stripped).
     *
     * @param string $pathorurl
     * @return string
     */
    protected static function guess_mimetype($pathorurl): string {
        if ($pathorurl === '') {
            return 'video/mp4';
        }
        $path = parse_url($pathorurl, PHP_URL_PATH);
        if (empty($path)) {
            $path = $pathorurl;
        }
        $mimetype = mimeinfo('type', $path);
        if (empty($mimetype) || $mimetype === 'document/unknown') {
            return 'video/mp4';
        }
        return $mimetype;
    }

    /**
     * Return the single uploaded video file for a module context, or null.
     *
     * @param \context_module $context
     * @return \stored_file|null
     */
    public static function get_uploaded_file($context) {
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $context->id,
            'mod_edztrackvideo',
            MOD_EDZTRACKVIDEO_FILEAREA,
            0,
            'itemid, filepath, filename',
            false
        );
        foreach ($files as $file) {
            if (!$file->is_directory()) {
                return $file;
            }
        }
        return null;
    }
}
