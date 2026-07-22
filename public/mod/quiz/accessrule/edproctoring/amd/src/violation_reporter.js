// This file is part of Moodle - http://moodle.org/
//
// quizaccess_edproctoring — AJAX violation reporter with retry queue.
//
// @package   quizaccess_edproctoring
// @copyright 2025 EDZLMS
// @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

define(['core/ajax'], function(Ajax) {

    'use strict';

    const MAX_RETRIES    = 3;
    const RETRY_DELAY_MS = 5000;

    /**
     * Send a violation to the server.
     * Retries up to MAX_RETRIES times on failure.
     *
     * @param {number} sessionId
     * @param {string} violationType
     * @param {string} details       JSON string
     * @param {number} quizPage
     * @param {number} elapsed       seconds into attempt
     * @param {number} [snapId=0]    associated snapshot ID
     * @param {number} [attempt=0]   retry count (internal)
     * @returns {Promise<Object>} {violationid, warningcount, autosubmit}
     */
    function log(sessionId, violationType, details, quizPage, elapsed, snapId = 0, attempt = 0) {
        return Ajax.call([{
            methodname: 'quizaccess_edproctoring_log_violation',
            args: {
                sessionid:     sessionId,
                violationtype: violationType,
                details:       details || '{}',
                quizpage:      quizPage || 0,
                elapsed:       elapsed  || 0,
                snapid:        snapId   || 0,
            },
        }])[0].catch(err => {
            if (attempt < MAX_RETRIES) {
                console.warn(`EDP reporter: retry ${attempt + 1}/${MAX_RETRIES} for ${violationType}`);
                return new Promise((resolve, reject) => {
                    setTimeout(() => {
                        log(sessionId, violationType, details, quizPage, elapsed, snapId, attempt + 1)
                            .then(resolve)
                            .catch(reject);
                    }, RETRY_DELAY_MS);
                });
            }
            // Exhausted retries. Return a safe fallback so callers don't crash.
            console.error('EDP reporter: violation log permanently failed', violationType, err);
            return {violationid: 0, warningcount: 0, autosubmit: false};
        });
    }

    return {log};
});
