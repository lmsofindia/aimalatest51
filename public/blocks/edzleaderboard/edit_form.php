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
 * EdzLeaderboard block — instance edit form.
 *
 * @package    block_edzleaderboard
 * @copyright  2026 EdzCorp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Per-instance configuration: title, layout and how many to show.
 */
class block_edzleaderboard_edit_form extends block_edit_form {

    /**
     * @param MoodleQuickForm $mform
     */
    protected function specific_definition($mform) {

        $mform->addElement('header', 'configheader',
            get_string('blocksettings', 'block'));

        // Title override.
        $mform->addElement('text', 'config_title',
            get_string('config_title', 'block_edzleaderboard'));
        $mform->setType('config_title', PARAM_TEXT);
        $mform->setDefault('config_title', get_string('defaulttitle', 'block_edzleaderboard'));

        // Layout: vertical (podium + list) or horizontal (split).
        $mform->addElement('select', 'config_layout',
            get_string('config_layout', 'block_edzleaderboard'), [
                'vertical'   => get_string('layout_vertical', 'block_edzleaderboard'),
                'horizontal' => get_string('layout_horizontal', 'block_edzleaderboard'),
            ]);
        $mform->setDefault('config_layout', 'vertical');

        // How many ranked users to show (podium counts toward this).
        $mform->addElement('text', 'config_count',
            get_string('config_count', 'block_edzleaderboard'));
        $mform->setType('config_count', PARAM_INT);
        $mform->setDefault('config_count', 10);
        $mform->addHelpButton('config_count', 'config_count', 'block_edzleaderboard');
    }
}
