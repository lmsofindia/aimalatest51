<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * EdzLeaderboard block — site-level settings.
 *
 * @package    block_edzleaderboard
 * @copyright  2026 EdzCorp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // The "course id" that Level Up! (block_xp) stores site-wide XP under.
    // In whole-site mode this is the site course (id 1), which matches the
    // ladder URL /blocks/xp/index.php/ladder/1. Exposed as a setting so it can
    // be pointed at a different id without touching code.
    $settings->add(new admin_setting_configtext(
        'block_edzleaderboard/xpcourseid',
        get_string('xpcourseid', 'block_edzleaderboard'),
        get_string('xpcourseiddesc', 'block_edzleaderboard'),
        '1',
        PARAM_INT
    ));
}
