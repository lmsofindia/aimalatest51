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
 * Category -> course cascade for the report pickers.
 *
 * @module     local_reportpanel/cascade
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import Ajax from 'core/ajax';
import Notification from 'core/notification';

export const init = (catId, courseId) => {
    const cat = document.getElementById(catId);
    const course = document.getElementById(courseId);
    if (!cat || !course) {
        return;
    }
    const placeholder = course.options.length ? course.options[0].textContent : '...';

    cat.addEventListener('change', () => {
        const categoryid = parseInt(cat.value, 10) || 0;
        course.innerHTML = '';
        const first = document.createElement('option');
        first.value = '0';
        first.textContent = placeholder;
        course.appendChild(first);
        if (!categoryid) {
            return;
        }
        Ajax.call([{
            methodname: 'local_reportpanel_get_courses',
            args: {categoryid: categoryid}
        }])[0].then((response) => {
            response.courses.forEach((c) => {
                const o = document.createElement('option');
                o.value = c.id;
                o.textContent = c.fullname;
                course.appendChild(o);
            });
            return response;
        }).catch(Notification.exception);
    });
};
