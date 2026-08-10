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
 * Potential user selector module.
 *
 * @module     local_cohortmanager/form-members-selector
 * @copyright  2025 Davison Ramos
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import Ajax from 'core/ajax';

const processResults = (selector, results) => {
    const users = [];
    $.each(results, (index, user) => {
        users.push({
            value: user.id,
            label: user.fullname
        });
    });
    return users;
};

const transport = (selector, query, success, failure) => {
    let promise;
    const cohortid = $(selector).attr('cohortid');

    promise = Ajax.call([{
        methodname: 'local_cohortmanager_get_potential_cohort_members',
        args: {
            cohortid: cohortid,
            query: query
        }
    }]);

    promise[0].then(async (results) => {
        success(results);
        return;
    }).fail(failure);
};

export { processResults, transport };
