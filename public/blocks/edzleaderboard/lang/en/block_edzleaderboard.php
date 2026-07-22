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
 * EdzLeaderboard block — English language strings.
 *
 * @package    block_edzleaderboard
 * @copyright  2026 EdzCorp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'EdzLeaderboard';
$string['defaulttitle'] = 'Leaderboard';

// Capabilities.
$string['edzleaderboard:addinstance'] = 'Add a new EdzLeaderboard block';
$string['edzleaderboard:myaddinstance'] = 'Add a new EdzLeaderboard block to the Dashboard';

// Instance settings.
$string['config_title'] = 'Block title';
$string['config_layout'] = 'Layout';
$string['layout_vertical'] = 'Vertical — podium + ranked list';
$string['layout_horizontal'] = 'Horizontal — top 3 left, the rest right';
$string['config_count'] = 'Users to show';
$string['config_count_help'] = 'How many ranked users to display in total, including the top three. Between 3 and 50.';

// Site settings.
$string['xpcourseid'] = 'Level Up! site-wide course id';
$string['xpcourseiddesc'] = 'The course id that Level Up! (block_xp) stores site-wide XP under. In whole-site mode this is the site course (id 1), matching the ladder URL /blocks/xp/index.php/ladder/1. Change only if your Level Up! is configured differently.';

// Presentational strings.
$string['pointssuffix'] = 'pts';
$string['levellabel'] = 'Level {$a}';
$string['youlabel'] = 'YOU';
$string['yourrank'] = 'Your rank';

// Empty / unavailable states.
$string['xpnotavailable'] = 'The leaderboard needs the Level Up! (block_xp) plugin, which is not installed yet.';
$string['noentries'] = 'No points have been earned yet — check back once learners start making progress.';

// Privacy — the block stores no personal data of its own; it only reads block_xp.
$string['privacy:metadata'] = 'The EdzLeaderboard block does not store any personal data. It displays ranking information sourced from the Level Up! (block_xp) plugin.';
