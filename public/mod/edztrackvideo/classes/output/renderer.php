<?php
/**
 * Renderer for mod_edztrackvideo.
 *
 * @package   mod_edztrackvideo
 */

namespace mod_edztrackvideo\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;
use mod_edztrackvideo\source_resolver;

class renderer extends plugin_renderer_base {

    /**
     * Render the student view.
     *
     * @param \stdClass $ed edztrackvideo record
     * @param \stdClass|\cm_info $cm
     * @param \context_module $context
     * @return string
     */
    public function render_view($ed, $cm, $context) {
        $data = new \stdClass();
        $data->id = (int)$ed->id;
        $data->name = format_string($ed->name);
        $data->intro = format_module_intro('edztrackvideo', $ed, $cm->id);
        $data->watermark = format_string($ed->name);

        $data->sourcetype = $ed->sourcetype;
        $data->is_youtube = ($ed->sourcetype === 'youtube');
        $data->is_vimeo = ($ed->sourcetype === 'vimeo');
        // HTML5 <video> sources: uploaded file, server file, or direct URL.
        $data->is_filevideo = in_array($ed->sourcetype, ['upload', 'serverfile', 'directurl'], true);
        $data->is_embed = in_array($ed->sourcetype, ['youtube', 'vimeo'], true);

        $data->videoid = (string)$ed->videoid;
        $data->allow_speed = (bool)$ed->allow_speed;

        // Resolve the file URL for the <video> element (any HTML5 source).
        $data->fileurl = '';
        $data->mimetype = '';
        if ($data->is_filevideo) {
            $config = source_resolver::build_player_config($ed, $cm, $context);
            $data->fileurl = $config['fileurl'];
            $data->mimetype = $config['mimetype'];
        }

        return $this->render_from_template('mod_edztrackvideo/view', $data);
    }
}
