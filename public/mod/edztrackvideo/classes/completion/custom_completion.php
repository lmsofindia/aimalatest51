<?php
/**
 * Custom completion support for mod_edztrackvideo.
 *
 * Single rule: 'completion_threshold' — mark complete after X% watched.
 *
 * @package   mod_edztrackvideo
 */

namespace mod_edztrackvideo\completion;

defined('MOODLE_INTERNAL') || die();

use core_completion\activity_custom_completion;

class custom_completion extends activity_custom_completion {

    /** The watch-percent rule id. */
    const RULE_WATCHPERCENT = 'completion_threshold';

    /**
     * Get the state of a custom rule for the configured user.
     *
     * @param string $rule
     * @return int COMPLETION_COMPLETE | COMPLETION_INCOMPLETE
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        $userid = $this->userid;
        $cm = $this->cm;
        if (empty($cm->instance)) {
            return COMPLETION_INCOMPLETE;
        }

        $rec = $DB->get_record('edztrackvideo_progress',
            ['edztrackvideoid' => $cm->instance, 'userid' => $userid],
            'maxwatched, duration, completed', IGNORE_MISSING);

        if (!$rec) {
            return COMPLETION_INCOMPLETE;
        }
        if (!empty($rec->completed)) {
            return COMPLETION_COMPLETE;
        }

        $dur = (float)($rec->duration ?? 0);
        if ($dur <= 0) {
            return COMPLETION_INCOMPLETE;
        }
        $percent = ((float)($rec->maxwatched ?? 0) / $dur) * 100.0;

        $threshold = $this->get_threshold();

        return ($percent >= $threshold) ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Resolve the configured threshold from cm customdata or DB.
     *
     * @return int
     */
    protected function get_threshold(): int {
        global $DB;
        if (!empty($this->cm->customdata['customcompletionrules']['completion_threshold'])) {
            return (int)$this->cm->customdata['customcompletionrules']['completion_threshold'];
        }
        $ed = $DB->get_record('edztrackvideo', ['id' => $this->cm->instance], 'completion_threshold', IGNORE_MISSING);
        return $ed ? (int)($ed->completion_threshold ?? 100) : 100;
    }

    /**
     * Defined rules for this activity.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return [self::RULE_WATCHPERCENT];
    }

    /**
     * Descriptions for the defined rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        $threshold = $this->get_threshold();
        return [
            self::RULE_WATCHPERCENT => get_string('completion_watchpercent', 'mod_edztrackvideo', $threshold),
        ];
    }

    /**
     * Sort order including the standard view rule.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            self::RULE_WATCHPERCENT,
        ];
    }
}
