<?php
defined('MOODLE_INTERNAL') || die();

class block_corpusercmplchart extends block_base {

    public function init(): void {
        $this->title = get_string('pluginname', 'block_corpusercmplchart');
    }

    public function hide_header(): bool { return true; }

    public function applicable_formats(): array {
        return ['my' => true, 'site-index' => true, 'course-view' => false];
    }

    public function has_config(): bool { return false; }
    public function instance_allow_config(): bool { return true; }

    public function get_content(): stdClass {
        global $USER, $OUTPUT, $PAGE, $CFG;

        if ($this->content !== null) {
            return $this->content;
        }
        $this->content         = new stdClass();
        $this->content->footer = '';

        if (!isloggedin() || isguestuser()) {
            $this->content->text = '';
            return $this->content;
        }

        // ── Live data ─────────────────────────────────────────────────────────
        $chartdata = \block_corpusercmplchart\external\get_completion_data::fetch_data();

        // ── Background gradient (configurable per block instance) ─────────────
        $grad1     = !empty($this->config->gradcolor1) ? clean_param($this->config->gradcolor1, PARAM_TEXT) : '#1a0533';
        $grad2     = !empty($this->config->gradcolor2) ? clean_param($this->config->gradcolor2, PARAM_TEXT) : '#0f172a';
        $gradangle = !empty($this->config->gradangle)  ? clean_param($this->config->gradangle,  PARAM_TEXT) : '145deg';

        $data = array_merge($chartdata, [
            'instanceid'      => $this->instance->id,
            'nocourses'       => get_string('nocourses', 'block_corpusercmplchart'),
            'detailsurl'      => (new moodle_url('/course/index.php'))->out(false),
            'gradcolor1'      => $grad1,
            'gradcolor2'      => $grad2,
            'gradangle'       => $gradangle,
        ]);

        // Pass raw numbers to JS for canvas rendering.
        $PAGE->requires->js_call_amd('block_corpusercmplchart/corpusercmplchart', 'init', [[
            'instanceid' => (int)$this->instance->id,
            'avgpct'     => (int)$chartdata['avgpct'],
            'modpct'     => (int)$chartdata['modpct'],
            'quizpct'    => (int)$chartdata['quizpct'],
        ]]);

        $this->content->text = $OUTPUT->render_from_template('block_corpusercmplchart/content', $data);
        return $this->content;
    }
}
