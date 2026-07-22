<?php
// This file is part of the local_edzfaculty plugin for Moodle.

namespace local_edzfaculty\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Access checks for the faculty dashboard.
 *
 * Teachers hold local/edzfaculty:view in their COURSE contexts (from the
 * teacher/editingteacher archetypes), not at the system context — so we must
 * check the capability across courses, not only at CONTEXT_SYSTEM.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class access {

    /**
     * Does this user have the dashboard view capability anywhere
     * (system-wide, or in at least one course they teach)?
     *
     * @param int $userid 0 = current user
     * @return bool
     */
    public static function is_teacher(int $userid = 0): bool {
        global $USER;
        $userid = $userid ?: $USER->id;

        if (has_capability('local/edzfaculty:view', \context_system::instance(), $userid)) {
            return true;
        }
        // Courses where the user holds the capability (doanything = false).
        $courses = get_user_capability_course('local/edzfaculty:view', $userid, false);
        return !empty($courses);
    }

    /**
     * Does this user hold the raw "view all" capability (manager archetype)?
     *
     * @param int $userid 0 = current user
     * @return bool
     */
    public static function can_view_all(int $userid = 0): bool {
        global $USER;
        $userid = $userid ?: $USER->id;
        return has_capability('local/edzfaculty:viewall', \context_system::instance(), $userid);
    }

    /**
     * Is this user treated as an admin viewer — sees the "All Faculty" overview
     * and may open other teachers' dashboards? Governed by the overviewaccess
     * setting so sites where faculty also hold Manager can restrict it to admins.
     *
     * @param int $userid 0 = current user
     * @return bool
     */
    public static function is_admin_viewer(int $userid = 0): bool {
        global $USER;
        $userid = $userid ?: $USER->id;
        $mode = get_config('local_edzfaculty', 'overviewaccess') ?: 'manager';
        if ($mode === 'admin') {
            return is_siteadmin($userid);
        }
        return self::can_view_all($userid);
    }

    /**
     * Throw unless the current user may see the faculty dashboard.
     *
     * @return void
     */
    public static function require_teacher(): void {
        if (!self::is_teacher() && !self::is_admin_viewer()) {
            throw new \required_capability_exception(
                \context_system::instance(), 'local/edzfaculty:view', 'nopermissions', '');
        }
    }
}
