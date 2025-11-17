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
 * TODO describe file block24
 *
 * @package    theme_edzsaas
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Block 24 heading.
$name = 'theme_edzsaas/block24info';
$heading = get_string('block24info', 'theme_edzsaas');
$information = get_string('block24infodesc', 'theme_edzsaas');
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// Enable/disable block.
$name = 'theme_edzsaas/block24enabled';
$title = get_string('block24enabled', 'theme_edzsaas');
$description = get_string('block24enableddesc', 'theme_edzsaas');
$setting = new admin_setting_configcheckbox($name, $title, $description, 1);
$page->add($setting);

// Get Moodle categories for dropdown.
$categories = core_course_category::make_categories_list();
$options = [];
foreach ($categories as $id => $namecat) {
    $options[$id] = $namecat;
}

// Category selection (multi-select).
$name = 'theme_edzsaas/block24categories';
$title = get_string('block24categories', 'theme_edzsaas');
$description = get_string('block24categoriesdesc', 'theme_edzsaas');
$setting = new admin_setting_configmultiselect($name, $title, $description, [], $options);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Header text.
$name = 'theme_edzsaas/block24header';
$title = get_string('block24header', 'theme_edzsaas');
$description = get_string('block24headerdesc', 'theme_edzsaas');
$default = get_string('block24headerdefault', 'theme_edzsaas');
$setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_TEXT);
$page->add($setting);

// Button text.
$name = 'theme_edzsaas/block24button';
$title = get_string('block24button', 'theme_edzsaas');
$description = get_string('block24buttondesc', 'theme_edzsaas');
$default = get_string('button24', 'theme_edzsaas');
$setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_TEXT);
$page->add($setting);

// Button URL.
$name = 'theme_edzsaas/block24buttonlink';
$title = get_string('block24buttonlink', 'theme_edzsaas');
$description = get_string('block24buttonlinkdesc', 'theme_edzsaas');
$default = 'course/index.php';
$setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_URL);
$page->add($setting);
