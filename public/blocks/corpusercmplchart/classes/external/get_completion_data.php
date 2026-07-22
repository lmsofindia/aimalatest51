<?php
namespace block_corpusercmplchart\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use context_system;

class get_completion_data extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Core data-fetching logic — no $PAGE interaction, safe to call from get_content().
     * Called by both execute() (AJAX) and block_corpusercmplchart::get_content() (page render).
     */
    public static function fetch_data(): array {
        global $USER, $DB, $CFG;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->libdir  . '/completionlib.php');

        $courses     = enrol_get_users_courses($USER->id, true, 'id,fullname,shortname,visible');
        $completed   = 0;
        $inprogress  = 0;
        $notstarted  = 0;
        $allpcts     = [];
        $totalmods   = 0;
        $donemods    = 0;
        $inprogressdetails = [];

        foreach ($courses as $course) {
            if (!$course->visible) { continue; }

            $info       = new \completion_info($course);
            $percent    = 0;
            $iscomplete = false;

            if ($info->is_enabled()) {
                $cc         = new \completion_completion(['userid' => $USER->id, 'course' => $course->id]);
                $iscomplete = $cc->is_complete();

                $modules = $info->get_activities();
                $done    = 0;
                foreach ($modules as $mod) {
                    $d = $info->get_data($mod, false, $USER->id);
                    if ($d->completionstate == COMPLETION_COMPLETE ||
                        $d->completionstate == COMPLETION_COMPLETE_PASS) {
                        $done++;
                        $donemods++;
                    }
                    $totalmods++;
                }
                $total   = count($modules);
                $percent = $total > 0 ? (int)round(($done / $total) * 100) : 0;
            }

            if ($iscomplete || $percent >= 100) {
                $status = 'completed'; $completed++;
            } elseif ($percent > 0) {
                $status = 'inprogress'; $inprogress++;
                $inprogressdetails[] = ['name' => format_string($course->fullname), 'percent' => $percent];
            } else {
                $status = 'notstarted'; $notstarted++;
            }
            $allpcts[] = $percent;
        }

        $total  = $completed + $inprogress + $notstarted;
        $avgpct = $total > 0 ? (int)round(array_sum($allpcts) / $total) : 0;
        $modpct = $totalmods > 0 ? (int)round(($donemods / $totalmods) * 100) : 0;

        // Quiz pass rate (gracefully skips if quiz not installed).
        $quizpct = 0;
        try {
            $quiztotal = (int)$DB->count_records_select('quiz_attempts',
                'userid = ? AND state = ?', [$USER->id, 'finished']);
            if ($quiztotal > 0) {
                $quizpassed = (int)$DB->count_records_sql(
                    "SELECT COUNT(qa.id) FROM {quiz_attempts} qa
                       JOIN {quiz} q ON q.id = qa.quiz
                      WHERE qa.userid = ? AND qa.state = 'finished'
                        AND qa.sumgrades >= (q.grade * 0.5)",
                    [$USER->id]);
                $quizpct = (int)round(($quizpassed / $quiztotal) * 100);
            }
        } catch (\dml_exception $e) {
            $quizpct = 0;
        }

        // Milestone: nearest in-progress course to completion.
        $milestonemsg    = '';
        $milestonedetail = '';
        if (!empty($inprogressdetails)) {
            usort($inprogressdetails, fn($a, $b) => $b['percent'] - $a['percent']);
            $nearest   = $inprogressdetails[0];
            $remaining = 100 - $nearest['percent'];
            $lessons   = max(1, (int)ceil($remaining / 10) * 2);
            $target    = min(100, (int)(ceil(($nearest['percent'] + 10) / 10) * 10));
            $milestonemsg    = get_string('milestone_message', 'block_corpusercmplchart',
                ['lessons' => $lessons]);
            $milestonedetail = get_string('milestone_detail', 'block_corpusercmplchart',
                ['course' => $nearest['name'], 'target' => $target]);
        }

        return [
            'total'           => $total,
            'completed'       => $completed,
            'inprogress'      => $inprogress,
            'notstarted'      => $notstarted,
            'avgpct'          => $avgpct,
            'modpct'          => $modpct,
            'quizpct'         => $quizpct,
            'completedpct'    => $total > 0 ? (int)round(($completed  / $total) * 100) : 0,
            'inprogresspct'   => $total > 0 ? (int)round(($inprogress / $total) * 100) : 0,
            'notstartedpct'   => $total > 0 ? (int)round(($notstarted / $total) * 100) : 0,
            'milestonemsg'    => $milestonemsg,
            'milestonedetail' => $milestonedetail,
            'hasmilestone'    => !empty($milestonemsg),
            'hascourses'      => $total > 0,
        ];
    }

    /**
     * AJAX entry point — validates context/auth, then delegates to fetch_data().
     */
    public static function execute(): array {
        self::validate_parameters(self::execute_parameters(), []);
        self::validate_context(context_system::instance());
        return self::fetch_data();
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'total'           => new external_value(PARAM_INT,  'Total courses'),
            'completed'       => new external_value(PARAM_INT,  'Completed'),
            'inprogress'      => new external_value(PARAM_INT,  'In progress'),
            'notstarted'      => new external_value(PARAM_INT,  'Not started'),
            'avgpct'          => new external_value(PARAM_INT,  'Avg course completion %'),
            'modpct'          => new external_value(PARAM_INT,  'Module completion %'),
            'quizpct'         => new external_value(PARAM_INT,  'Quiz pass rate %'),
            'completedpct'    => new external_value(PARAM_INT,  'Completed % of total'),
            'inprogresspct'   => new external_value(PARAM_INT,  'In-progress % of total'),
            'notstartedpct'   => new external_value(PARAM_INT,  'Not-started % of total'),
            'milestonemsg'    => new external_value(PARAM_TEXT, 'Milestone message'),
            'milestonedetail' => new external_value(PARAM_TEXT, 'Milestone detail'),
            'hasmilestone'    => new external_value(PARAM_BOOL, 'Has milestone'),
            'hascourses'      => new external_value(PARAM_BOOL, 'Has courses'),
        ]);
    }
}
