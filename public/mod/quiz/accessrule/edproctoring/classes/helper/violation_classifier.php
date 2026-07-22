<?php
// This file is part of Moodle - http://moodle.org/

namespace quizaccess_edproctoring\helper;

/**
 * Violation taxonomy: types, severities, and trust score weights.
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class violation_classifier {

    // Violation type constants.
    const FACE_ABSENT      = 'FACE_ABSENT';
    const MULTIPLE_FACES   = 'MULTIPLE_FACES';
    const FACE_MISMATCH    = 'FACE_MISMATCH';   // Phase 2
    const TAB_SWITCH       = 'TAB_SWITCH';
    const FULLSCREEN_EXIT  = 'FULLSCREEN_EXIT';
    const COPY_PASTE       = 'COPY_PASTE';
    const LOW_LIGHT        = 'LOW_LIGHT';
    const CAMERA_BLOCKED   = 'CAMERA_BLOCKED';
    const IDLE             = 'IDLE';

    // Severity levels.
    const SEVERITY_CRITICAL = 'critical';
    const SEVERITY_WARNING  = 'warning';
    const SEVERITY_INFO     = 'info';

    /**
     * Full violation taxonomy: type => [severity, weight].
     * Weight is deducted from the 100-point trust score per occurrence.
     */
    const TAXONOMY = [
        self::FACE_ABSENT     => [self::SEVERITY_CRITICAL, 10.0],
        self::MULTIPLE_FACES  => [self::SEVERITY_CRITICAL, 15.0],
        self::FACE_MISMATCH   => [self::SEVERITY_CRITICAL, 20.0],
        self::TAB_SWITCH      => [self::SEVERITY_WARNING,   8.0],
        self::FULLSCREEN_EXIT => [self::SEVERITY_WARNING,   5.0],
        self::COPY_PASTE      => [self::SEVERITY_WARNING,   3.0],
        self::LOW_LIGHT       => [self::SEVERITY_INFO,      2.0],
        self::CAMERA_BLOCKED  => [self::SEVERITY_CRITICAL, 12.0],
        self::IDLE            => [self::SEVERITY_INFO,      1.0],
    ];

    /**
     * Return the severity for a given violation type.
     *
     * @param string $type
     * @return string severity constant
     * @throws \coding_exception if type is invalid
     */
    public static function get_severity(string $type): string {
        if (!isset(self::TAXONOMY[$type])) {
            throw new \coding_exception("Unknown violation type: $type");
        }
        return self::TAXONOMY[$type][0];
    }

    /**
     * Return the trust score weight for a given violation type.
     *
     * @param string $type
     * @return float
     * @throws \coding_exception
     */
    public static function get_weight(string $type): float {
        if (!isset(self::TAXONOMY[$type])) {
            throw new \coding_exception("Unknown violation type: $type");
        }
        // Admin-configured deduction (Site admin → Plugins → Quiz access rules →
        // Ed Proctoring) overrides the built-in default.
        $configured = get_config('quizaccess_edproctoring', 'weight_' . strtolower($type));
        if ($configured !== false && $configured !== '' && is_numeric($configured)
                && (float)$configured >= 0) {
            return (float)$configured;
        }
        return self::TAXONOMY[$type][1];
    }

    /**
     * Validate a violation type string.
     *
     * @param string $type
     * @return bool
     */
    public static function is_valid(string $type): bool {
        return isset(self::TAXONOMY[$type]);
    }

    /**
     * Return all types of a given severity.
     *
     * @param string $severity
     * @return string[]
     */
    public static function get_types_by_severity(string $severity): array {
        return array_keys(array_filter(self::TAXONOMY,
            fn($v) => $v[0] === $severity));
    }
}
