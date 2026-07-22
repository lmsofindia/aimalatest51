<?php
// This file is part of Moodle - http://moodle.org/

namespace quizaccess_edproctoring\helper;

/**
 * Detects mobile / tablet browsers via User-Agent.
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile_detector {

    /**
     * Determine if the current request comes from a mobile or tablet browser.
     * Uses PHP $_SERVER['HTTP_USER_AGENT'] — server-side check can't be spoofed
     * by student-facing JS manipulation.
     *
     * @return bool
     */
    public static function is_mobile(): bool {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (empty($ua)) {
            return false;
        }

        $mobilePatterns = [
            '/android/i',
            '/webos/i',
            '/iphone/i',
            '/ipad/i',
            '/ipod/i',
            '/blackberry/i',
            '/windows phone/i',
            '/mobile/i',
            '/tablet/i',
            '/opera mini/i',
        ];

        foreach ($mobilePatterns as $pattern) {
            if (preg_match($pattern, $ua)) {
                return true;
            }
        }

        return false;
    }
}
