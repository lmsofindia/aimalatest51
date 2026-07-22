<?php
defined('MOODLE_INTERNAL') || die();

class block_corpusertimeline extends block_base {

    public function init(): void {
        $this->title = get_string('pluginname', 'block_corpusertimeline');
    }

    public function hide_header(): bool { return true; }

    public function applicable_formats(): array {
        return ['my' => true, 'site-index' => true, 'course-view' => false];
    }

    public function get_content(): stdClass {
        global $USER, $DB, $OUTPUT, $PAGE, $CFG;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content         = new stdClass();
        $this->content->footer = '';

        if (!isloggedin() || isguestuser()) {
            $this->content->text = '';
            return $this->content;
        }


        // ── DEMO MODE ─────────────────────────────────────────────────────────
        // Set to false (or remove) when live data is ready.
        $demo = true;
        if ($demo) {
            $svgmap = \block_corpusertimeline\external\get_timeline::get_icon_svg_map_public();
            $today    = 'Today · ' . userdate(time(), '%a, %b %e');
            $yest     = 'Yesterday · ' . userdate(time() - DAYSECS, '%a, %b %e');
            $data = [
                'title'       => get_string('activitytimeline', 'block_corpusertimeline'),
                'sessioninfo' => get_string('lastsessions', 'block_corpusertimeline', 15),
                'eventcount'  => get_string('events', 'block_corpusertimeline', 24),
                'hasgroups'   => true,
                'hasmore'     => true,
                'instanceid'  => $this->instance->id,
                'groups' => [
                    ['daylabel' => $today, 'events' => [
                        ['label'=>'Completed H5P',        'iconkey'=>'h5p',      'iconsvg'=>$svgmap['h5p'],      'coursename'=>'Digital Marketing Fundamentals', 'timestr'=>'10:45 AM', 'ispassed'=>false,'isbelow'=>false,'badge'=>''],
                        ['label'=>'Submitted quiz',       'iconkey'=>'quiz',     'iconsvg'=>$svgmap['quiz'],     'coursename'=>'Advanced PHP Development',       'timestr'=>'10:12 AM', 'ispassed'=>true, 'isbelow'=>false,'badge'=>'passed'],
                        ['label'=>'Viewed activity',      'iconkey'=>'view',     'iconsvg'=>$svgmap['view'],     'coursename'=>'Advanced PHP Development',       'timestr'=>' 9:55 AM', 'ispassed'=>false,'isbelow'=>false,'badge'=>''],
                        ['label'=>'Logged in',            'iconkey'=>'login',    'iconsvg'=>$svgmap['login'],    'coursename'=>'',                               'timestr'=>' 9:50 AM', 'ispassed'=>false,'isbelow'=>false,'badge'=>''],
                    ]],
                    ['daylabel' => $yest, 'events' => [
                        ['label'=>'Earned badge',         'iconkey'=>'badge',    'iconsvg'=>$svgmap['badge'],    'coursename'=>'Leadership Essentials',          'timestr'=>' 4:20 PM', 'ispassed'=>false,'isbelow'=>false,'badge'=>''],
                        ['label'=>'Submitted assignment', 'iconkey'=>'assign',   'iconsvg'=>$svgmap['assign'],   'coursename'=>'Business Communication',         'timestr'=>' 3:05 PM', 'ispassed'=>false,'isbelow'=>false,'badge'=>''],
                        ['label'=>'Posted in forum',      'iconkey'=>'forum',    'iconsvg'=>$svgmap['forum'],    'coursename'=>'Data Science Basics',            'timestr'=>' 2:15 PM', 'ispassed'=>false,'isbelow'=>false,'badge'=>''],
                        ['label'=>'Submitted quiz',       'iconkey'=>'quiz',     'iconsvg'=>$svgmap['quiz'],     'coursename'=>'Excel for Analysts',             'timestr'=>'11:30 AM', 'ispassed'=>false,'isbelow'=>true, 'badge'=>'below'],
                        ['label'=>'Viewed course',        'iconkey'=>'course',   'iconsvg'=>$svgmap['course'],   'coursename'=>'Excel for Analysts',             'timestr'=>'11:10 AM', 'ispassed'=>false,'isbelow'=>false,'badge'=>''],
                        ['label'=>'Completed course',     'iconkey'=>'complete', 'iconsvg'=>$svgmap['complete'], 'coursename'=>'Leadership Essentials',          'timestr'=>'10:00 AM', 'ispassed'=>false,'isbelow'=>false,'badge'=>''],
                    ]],
                ],
            ];
            $PAGE->requires->js_call_amd('block_corpusertimeline/corpusertimeline', 'init',
                [['instanceid' => (int)$this->instance->id]]);
            $this->content->text = $OUTPUT->render_from_template('block_corpusertimeline/content', $data);
            return $this->content;
        }
        // ── END DEMO MODE ─────────────────────────────────────────────────────

        // Load initial batch via shared helper — bypasses validate_context() which
        // must NOT be called after output has started (AJAX-only guard).
        $extdata = \block_corpusertimeline\external\get_timeline::fetch_data(0, 15, 'week');

        // Count events this week and total.
        $weekcount = $DB->count_records_select(
            'logstore_standard_log',
            'userid = ? AND timecreated >= ?',
            [$USER->id, strtotime('monday this week midnight')]
        );

        $data = [
            'title'       => get_string('activitytimeline', 'block_corpusertimeline'),
            'sessioninfo' => get_string('lastsessions', 'block_corpusertimeline', 15),
            'eventcount'  => get_string('events', 'block_corpusertimeline', $weekcount),
            'groups'      => $extdata['groups'],
            'hasgroups'   => !empty($extdata['groups']),
            'hasmore'     => $extdata['hasmore'],
            'instanceid'  => $this->instance->id,
        ];

        $PAGE->requires->js_call_amd('block_corpusertimeline/corpusertimeline', 'init',
            [['instanceid' => (int)$this->instance->id]]);

        $this->content->text = $OUTPUT->render_from_template('block_corpusertimeline/content', $data);
        return $this->content;
    }
}
