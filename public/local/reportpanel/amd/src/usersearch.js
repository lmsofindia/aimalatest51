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
 * User typeahead for the consolidated report (full mode).
 *
 * @module     local_reportpanel/usersearch
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import Ajax from 'core/ajax';
import Notification from 'core/notification';

export const init = (inputId, resultsId, baseurl) => {
    const input = document.getElementById(inputId);
    const results = document.getElementById(resultsId);
    if (!input || !results) {
        return;
    }
    let timer = null;

    const hide = () => {
        results.hidden = true;
        results.innerHTML = '';
    };

    const render = (users) => {
        results.innerHTML = '';
        if (!users.length) {
            hide();
            return;
        }
        users.forEach((u) => {
            const item = document.createElement('a');
            item.href = baseurl + (baseurl.indexOf('?') === -1 ? '?' : '&') + 'userid=' + u.id;
            item.className = 'list-group-item list-group-item-action';
            const name = document.createElement('span');
            name.textContent = u.fullname;
            const email = document.createElement('small');
            email.className = 'text-muted ml-2';
            email.textContent = u.email;
            item.appendChild(name);
            item.appendChild(email);
            results.appendChild(item);
        });
        results.hidden = false;
    };

    input.addEventListener('input', () => {
        const query = input.value.trim();
        if (timer) {
            window.clearTimeout(timer);
        }
        if (query.length < 2) {
            hide();
            return;
        }
        timer = window.setTimeout(() => {
            Ajax.call([{
                methodname: 'local_reportpanel_search_users',
                args: {query: query}
            }])[0].then((response) => {
                render(response.users);
                return response;
            }).catch(Notification.exception);
        }, 250);
    });

    document.addEventListener('click', (e) => {
        if (e.target !== input && !results.contains(e.target)) {
            hide();
        }
    });
};
