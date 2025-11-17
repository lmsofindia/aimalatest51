<?php

namespace local_coursefilter\output;

defined('MOODLE_INTERNAL') || die();

class renderer extends \plugin_renderer_base
{

    public function render_index_page(array $data): string
    {
        return $this->render_from_template('local_coursefilter/index', $data);
    }

    public function render_coursegrid(array $data): string
    {
        return $this->render_from_template('local_coursefilter/coursegrid', $data);
    }
}
