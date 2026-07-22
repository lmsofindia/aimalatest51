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
 * Instance configuration form for block_corpusercmplchart.
 *
 * @package    block_corpusercmplchart
 * @copyright  2026 EDZLMS <marketing@edzlms.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_corpusercmplchart_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        // Gradient start colour.
        $mform->addElement('text', 'config_gradcolor1',
            get_string('configgradcolor1', 'block_corpusercmplchart'), ['size' => 10]);
        $mform->setType('config_gradcolor1', PARAM_TEXT);
        $mform->setDefault('config_gradcolor1', '#1a0533');
        $mform->addHelpButton('config_gradcolor1', 'configgradcolor1', 'block_corpusercmplchart');

        // Gradient end colour.
        $mform->addElement('text', 'config_gradcolor2',
            get_string('configgradcolor2', 'block_corpusercmplchart'), ['size' => 10]);
        $mform->setType('config_gradcolor2', PARAM_TEXT);
        $mform->setDefault('config_gradcolor2', '#0f172a');
        $mform->addHelpButton('config_gradcolor2', 'configgradcolor2', 'block_corpusercmplchart');

        // Gradient direction.
        $angles = [
            '145deg' => get_string('gradangle_diag1', 'block_corpusercmplchart'),
            '215deg' => get_string('gradangle_diag2', 'block_corpusercmplchart'),
            '90deg'  => get_string('gradangle_horiz', 'block_corpusercmplchart'),
            '180deg' => get_string('gradangle_vert',  'block_corpusercmplchart'),
        ];
        $mform->addElement('select', 'config_gradangle',
            get_string('configgradangle', 'block_corpusercmplchart'), $angles);
        $mform->setType('config_gradangle', PARAM_TEXT);
        $mform->setDefault('config_gradangle', '145deg');
    }
}
