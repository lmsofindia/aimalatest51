<?php
namespace quizaccess_edproctoring\event;
defined('MOODLE_INTERNAL') || die();

class session_completed extends \core\event\base {
    protected function init() {
        $this->data['crud']        = 'u';
        $this->data['edulevel']    = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'quizaccess_edproctoring_session';
    }
    public static function get_name(): string {
        return get_string('pluginname', 'quizaccess_edproctoring') . ': session completed';
    }
    public function get_description(): string {
        $score = round($this->other['trust_score'] ?? 0, 1);
        return "Proctoring session {$this->objectid} completed for user {$this->userid}. Trust score: {$score}%.";
    }
    public function get_url(): \moodle_url {
        return new \moodle_url('/mod/quiz/accessrule/edproctoring/report/index.php',
            ['sessionid' => $this->objectid]);
    }
}
