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

namespace local_cohortmanager\admin;


use admin_setting_configcheckbox;

/**
 * Class admin_setting_allowcohortthemes
 *
 * @package    local_cohortmanager
 * @copyright  2025 Davison Almeida <ramosdealmeidasistemas@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_allowcohortthemes extends admin_setting_configcheckbox
{
    public function __construct($name, $visiblename, $description, $defaultsetting)
    {
        parent::__construct($name, $visiblename, $description, $defaultsetting);
    }

    public function write_setting($data)
    {
        $data = (int) $data;
        $saved = set_config('allowcohortthemes', $data, null, true);
        $newvalue = (int) get_config('core', 'allowcohortthemes');

        return $saved && $newvalue == $data ? '' : get_string('errorsetting', 'admin');
    }
}
