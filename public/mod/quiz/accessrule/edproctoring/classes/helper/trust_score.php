<?php
// This file is part of Moodle - http://moodle.org/

namespace quizaccess_edproctoring\helper;

/**
 * Trust score calculator.
 *
 * Formula: trust_score = max(0, 100 − Σ(violation.severity_weight))
 * Dismissed violations are excluded.
 *
 * Bands:
 *   80–100 = low_risk   (green)
 *   60–79  = review     (amber)
 *   0–59   = high_risk  (red)
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class trust_score {

    const BAND_LOW_RISK  = 'low_risk';
    const BAND_REVIEW    = 'review';
    const BAND_HIGH_RISK = 'high_risk';

    /**
     * Calculate the trust score for a session.
     * Reads all non-dismissed violations from DB.
     *
     * @param int $sessionid
     * @return float trust score 0.00–100.00
     */
    public static function calculate(int $sessionid): float {
        global $DB;

        $violations = $DB->get_records('quizaccess_edproctoring_violation',
            ['sessionid' => $sessionid, 'dismissed' => 0],
            '',
            'severity_weight'
        );

        $totalDeduction = 0.0;
        foreach ($violations as $v) {
            $totalDeduction += (float) $v->severity_weight;
        }

        return max(0.0, round(100.0 - $totalDeduction, 2));
    }

    /**
     * Return the band name for a given score.
     *
     * @param float $score
     * @return string band constant
     */
    public static function get_band(float $score): string {
        if ($score >= 80) {
            return self::BAND_LOW_RISK;
        }
        if ($score >= 60) {
            return self::BAND_REVIEW;
        }
        return self::BAND_HIGH_RISK;
    }

    /**
     * Return a Bootstrap colour class for the band (for report rendering).
     *
     * @param string $band
     * @return string CSS class
     */
    public static function get_band_class(string $band): string {
        return match($band) {
            self::BAND_LOW_RISK  => 'text-success',
            self::BAND_REVIEW    => 'text-warning',
            self::BAND_HIGH_RISK => 'text-danger',
            default              => 'text-muted',
        };
    }

    /**
     * Get violation breakdown for report display.
     * Returns each violation type with count and total weight contribution.
     *
     * @param int $sessionid
     * @return array [{type, severity, count, total_weight}]
     */
    public static function get_breakdown(int $sessionid): array {
        global $DB;

        $sql = "SELECT violation_type, severity,
                       COUNT(*) AS violation_count,
                       SUM(severity_weight) AS total_weight
                  FROM {quizaccess_edproctoring_violation}
                 WHERE sessionid = :sid AND dismissed = 0
                 GROUP BY violation_type, severity
                 ORDER BY total_weight DESC";

        return array_values($DB->get_records_sql($sql, ['sid' => $sessionid]));
    }
}
