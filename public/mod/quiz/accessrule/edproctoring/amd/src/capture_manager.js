// This file is part of Moodle - http://moodle.org/
//
// quizaccess_edproctoring — Webcam capture manager.
// Handles interval captures and burst captures on violation.
// Sends PNG base64 snapshots via save_snapshot external API.
//
// @package   quizaccess_edproctoring
// @copyright 2025 EDZLMS
// @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

define(['core/ajax'], function(Ajax) {

    'use strict';

    const BURST_COUNT    = 3;
    const BURST_DELAY_MS = 2000;  // 2 seconds between burst shots.

    let videoEl      = null;
    let sessionId    = 0;
    let intervalId   = null;
    let getElapsed   = () => 0;
    let getPage      = () => 0;

    /**
     * Capture the current video frame and return it as a base64 PNG string.
     *
     * @returns {string|null} Base64 PNG (no data: prefix) or null if no frame available.
     */
    function captureFrame() {
        if (!videoEl || videoEl.readyState < 2) {
            return null;
        }

        const canvas  = document.createElement('canvas');
        canvas.width  = videoEl.videoWidth  || 640;
        canvas.height = videoEl.videoHeight || 480;
        const ctx     = canvas.getContext('2d');
        ctx.drawImage(videoEl, 0, 0, canvas.width, canvas.height);

        // toDataURL returns "data:image/png;base64,..." — strip prefix.
        const dataUrl = canvas.toDataURL('image/png', 0.8);
        return dataUrl.replace(/^data:image\/\w+;base64,/, '');
    }

    /**
     * Send a snapshot to the server via AJAX.
     *
     * @param {string}  base64data
     * @param {string}  captureType  'interval'|'burst'|'prequiz'|'manual'
     * @param {boolean} isViolation
     * @returns {Promise<Object>} {snapid, success}
     */
    function sendSnapshot(base64data, captureType, isViolation) {
        if (!base64data) {
            return Promise.resolve({snapid: 0, success: false});
        }

        return Ajax.call([{
            methodname: 'quizaccess_edproctoring_save_snapshot',
            args: {
                sessionid:    sessionId,
                imagedata:    base64data,
                capturetype:  captureType,
                elapsed:      getElapsed(),
                quizpage:     getPage(),
                facedetected: -1,   // face-api.js result not passed here (separate module)
                facecount:    0,
                brightness:   -1,
                isviolation:  isViolation ? 1 : 0,
            },
        }])[0].catch(err => {
            console.warn('EDP capture: snapshot upload failed, will not retry', err);
            return {snapid: 0, success: false};
        });
    }

    /**
     * Take one interval snapshot and send it.
     */
    function intervalCapture() {
        const base64 = captureFrame();
        sendSnapshot(base64, 'interval', false);
    }

    /**
     * Trigger a burst of BURST_COUNT snapshots spaced BURST_DELAY_MS apart.
     * Called when a violation is detected.
     *
     * @param {number}  elapsed     seconds into attempt
     * @param {number}  page        current quiz page
     * @param {boolean} isViolation whether this is a violation burst
     * @returns {Promise<Object>} resolves with last snapid
     */
    function triggerBurst(elapsed, page, isViolation = true) {
        let shotsFired = 0;
        return new Promise((resolve) => {
            function fireShot() {
                const base64 = captureFrame();
                sendSnapshot(base64, 'burst', isViolation).then(result => {
                    shotsFired++;
                    if (shotsFired < BURST_COUNT) {
                        setTimeout(fireShot, BURST_DELAY_MS);
                    } else {
                        resolve(result);
                    }
                });
            }
            fireShot();
        });
    }

    /**
     * Initialise the capture manager.
     *
     * @param {HTMLVideoElement} video
     * @param {Object} opts
     * @param {number} opts.sessionId
     * @param {number} opts.captureInterval  seconds
     * @param {Function} opts.getElapsed
     * @param {Function} opts.getPage
     */
    function init(video, opts) {
        videoEl    = video;
        sessionId  = opts.sessionId;
        getElapsed = opts.getElapsed || (() => 0);
        getPage    = opts.getPage    || (() => 0);

        const intervalMs = (opts.captureInterval || 30) * 1000;
        intervalId = setInterval(intervalCapture, intervalMs);

        // Immediate identity snapshot at attempt start, so even very short
        // attempts have at least one image. Retries until the video element
        // has its first frame (readyState >= 2).
        let firstTries = 0;
        const firstShot = function() {
            const frame = captureFrame();
            if (frame) {
                sendSnapshot(frame, 'prequiz', false);
                console.log('EDP capture: initial pre-quiz snapshot sent');
            } else if (firstTries++ < 10) {
                setTimeout(firstShot, 1000);
            }
        };
        setTimeout(firstShot, 1500);

        console.log(`EDP capture: interval ${opts.captureInterval}s started`);
    }

    /**
     * Stop interval capture.
     */
    function stop() {
        if (intervalId) {
            clearInterval(intervalId);
            intervalId = null;
        }
    }

    return {init, stop, triggerBurst, captureFrame, sendSnapshot};
});
