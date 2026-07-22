<?php
namespace quizaccess_edproctoring\event;
defined('MOODLE_INTERNAL') || die();

class violation_logged extends \core\event\base {
    protected function init() {
        $this->data['crud']        = 'c';
        $this->data['edulevel']    = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'quizaccess_edproctoring_violation';
    }
    public static function get_name(): string {
        return get_string('pluginname', 'quizaccess_edproctoring') . ': violation logged';
    }
    public function get_description(): string {
        return "Violation '{$this->other['violation_type']}' ({$this->other['severity']}) logged for user {$this->userid}.";
    }
    public function get_url(): \moodle_url {
        return new \moodle_url('/mod/quiz/report.php',
            ['id' => $this->contextinstanceid, 'mode' => 'edproctoring']);
    }
}
