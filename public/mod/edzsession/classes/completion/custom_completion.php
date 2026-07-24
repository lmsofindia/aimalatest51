<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\completion;

use core_completion\activity_custom_completion;

/**
 * Attendance-driven custom completion for mod_edzsession.
 *
 * Rules (each enabled rule must pass — ALL):
 *  - completionattendancepercent: attended >= X% of session duration.
 *  - completionminutes: attended >= N minutes total.
 *  - completionsessions: attended >= N of M occurrences.
 *
 * P1: rule plumbing + state scaffolding. Real aggregation lands with the
 * attendance engine (P3); until then rules report INCOMPLETE safely.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {

    public function get_state(string $rule): int {
        $this->validate_rule($rule);
        global $DB;

        $edzsession = $DB->get_record('edzsession',
            ['id' => $this->cm->instance], '*', MUST_EXIST);
        $userid = (int) $this->userid;

        // Aggregate this user's attendance across all occurrences of the activity.
        $sql = "SELECT COUNT(a.id) AS sessionsattended,
                       COALESCE(SUM(a.joinseconds), 0) AS totalseconds,
                       COALESCE(SUM(a.attendedpercent), 0) AS sumpercent
                  FROM {edzsession_attendance} a
                  JOIN {edzsession_occurrence} o ON o.id = a.occurrenceid
                 WHERE o.edzsessionid = :eid
                   AND a.userid = :uid
                   AND a.joinseconds > 0";
        $agg = $DB->get_record_sql($sql, ['eid' => $edzsession->id, 'uid' => $userid]);
        $sessionsattended = (int) ($agg->sessionsattended ?? 0);
        $totalminutes = (int) floor(((int) ($agg->totalseconds ?? 0)) / 60);

        // Percentage is averaged over ALL occurrences (missed sessions count as
        // 0%), not just the ones attended — so one perfect session can't pass it.
        $occurrencecount = $DB->count_records('edzsession_occurrence', ['edzsessionid' => $edzsession->id]);
        $avgpercent = $occurrencecount > 0
            ? ((float) ($agg->sumpercent ?? 0) / $occurrencecount) : 0.0;

        switch ($rule) {
            case 'completionattendancepercent':
                $required = (int) $edzsession->completionattendancepercent;
                if ($required <= 0) {
                    return COMPLETION_COMPLETE; // Rule not in force.
                }
                return $avgpercent >= $required ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;

            case 'completionminutes':
                $required = (int) $edzsession->completionminutes;
                if ($required <= 0) {
                    return COMPLETION_COMPLETE;
                }
                return $totalminutes >= $required ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;

            case 'completionsessions':
                $required = (int) $edzsession->completionsessions;
                if ($required <= 0) {
                    return COMPLETION_COMPLETE;
                }
                return $sessionsattended >= $required ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
        }
        return COMPLETION_INCOMPLETE;
    }

    public static function get_defined_custom_rules(): array {
        return [
            'completionattendancepercent',
            'completionminutes',
            'completionsessions',
        ];
    }

    public function get_custom_rule_descriptions(): array {
        $percent = (int) ($this->cm->customdata['customcompletionrules']['completionattendancepercent'] ?? 0);
        $minutes = (int) ($this->cm->customdata['customcompletionrules']['completionminutes'] ?? 0);
        $sessions = (int) ($this->cm->customdata['customcompletionrules']['completionsessions'] ?? 0);
        return [
            'completionattendancepercent' =>
                get_string('completiondetail:percent', 'mod_edzsession', $percent),
            'completionminutes' =>
                get_string('completiondetail:minutes', 'mod_edzsession', $minutes),
            'completionsessions' =>
                get_string('completiondetail:sessions', 'mod_edzsession', $sessions),
        ];
    }

    public function get_sort_order(): array {
        return [
            'completionview',
            'completionattendancepercent',
            'completionminutes',
            'completionsessions',
        ];
    }
}
