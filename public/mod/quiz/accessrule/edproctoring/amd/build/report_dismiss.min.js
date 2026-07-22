// This file is part of Moodle - http://moodle.org/
//
// quizaccess_edproctoring — Dismiss-violation handler for the teacher report.
// Binds the .edp-dismiss buttons, calls the dismiss_violation web service,
// then reloads so the trust score and counters refresh.
//
// @package   quizaccess_edproctoring
// @copyright 2025 EDZLMS
// @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

define(['core/ajax', 'core/notification'], function(Ajax, Notification) {

    'use strict';

    function init() {
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.edp-dismiss');
            if (!btn) {
                return;
            }
            e.preventDefault();

            const id = parseInt(btn.dataset.id, 10);
            if (!id || btn.dataset.busy) {
                return;
            }
            btn.dataset.busy = '1';
            btn.textContent = '…';

            Ajax.call([{
                methodname: 'quizaccess_edproctoring_dismiss_violation',
                args: {violationid: id},
            }])[0].then(function() {
                // Reload so the summary card, counts and trust score update too.
                window.location.reload();
                return null;
            }).catch(function(err) {
                delete btn.dataset.busy;
                btn.textContent = 'Dismiss';
                Notification.exception(err);
            });
        });
    }

    return {init: init};
});
