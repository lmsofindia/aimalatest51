<?php
// This file is part of the local_edzfaculty plugin for Moodle.

namespace local_edzfaculty\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Rule-based at-risk scoring. Weights are proportional (Vidya lesson #1);
 * thresholds come from plugin settings.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class atrisk {

    /** Signal weights (sum = 100). */
    const W_ATTENDANCE = 25;
    const W_LOGIN      = 20;
    const W_SCOREDROP  = 25;
    const W_MISSED     = 15;
    const W_LOWSCORE   = 15;

    /**
     * Read configured thresholds with sane defaults.
     *
     * @return array
     */
    public static function config(): array {
        return [
            'minattendance'      => (int)(get_config('local_edzfaculty', 'minattendance') ?: 60),
            'maxloginstale'      => (int)(get_config('local_edzfaculty', 'maxloginstale') ?: 10),
            'minscore'           => (int)(get_config('local_edzfaculty', 'minscore') ?: 40),
            'scoredrop'          => (int)(get_config('local_edzfaculty', 'scoredrop') ?: 15),
            'maxmisseddeadlines' => (int)(get_config('local_edzfaculty', 'maxmisseddeadlines') ?: 2),
        ];
    }

    /**
     * Compute risk score, level and reasons for a per-student metrics row.
     *
     * @param object $m metrics: attendancepct, avgscore, scoretrend, lastaccess, misseddeadlines
     * @param array $cfg thresholds from config()
     * @return array [int score, string level, string[] reasons]
     */
    public static function score(object $m, array $cfg): array {
        $score   = 0;
        $reasons = [];

        $daysstale = !empty($m->lastaccess) ? (int)floor((time() - $m->lastaccess) / DAYSECS) : 999;
        if ($daysstale > $cfg['maxloginstale']) {
            $score += self::W_LOGIN;
            $reasons[] = get_string('reason_nologin', 'local_edzfaculty', $daysstale);
        }
        if ($m->attendancepct !== null && $m->attendancepct < $cfg['minattendance']) {
            $score += self::W_ATTENDANCE;
            $reasons[] = get_string('reason_lowattendance', 'local_edzfaculty', $cfg['minattendance']);
        }
        if ($m->scoretrend !== null && $m->scoretrend <= -$cfg['scoredrop']) {
            $score += self::W_SCOREDROP;
            $reasons[] = get_string('reason_scoredrop', 'local_edzfaculty', abs((int)round($m->scoretrend)));
        }
        if ($m->avgscore !== null && $m->avgscore < $cfg['minscore']) {
            $score += self::W_LOWSCORE;
            $reasons[] = get_string('reason_lowscore', 'local_edzfaculty', $cfg['minscore']);
        }
        if ((int)$m->misseddeadlines >= $cfg['maxmisseddeadlines']) {
            $score += self::W_MISSED;
            $reasons[] = get_string('reason_misseddeadlines', 'local_edzfaculty', (int)$m->misseddeadlines);
        }

        $score = min(100, $score);
        $level = $score >= 60 ? 'critical' : ($score >= 35 ? 'watch' : 'ok');
        return [$score, $level, $reasons];
    }
}
