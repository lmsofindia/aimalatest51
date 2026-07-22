// This file is part of Moodle - http://moodle.org/
//
// quizaccess_edproctoring — face-api.js wrapper for real-time face detection.
// Uses vladmandic/face-api TinyFaceDetector model for low CPU footprint.
//
// @package   quizaccess_edproctoring
// @copyright 2025 EDZLMS
// @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

define([
    'quizaccess_edproctoring/vendor/faceapi-lazy',
], function(faceapi) {

    'use strict';

    const MODEL_URL   = M.cfg.wwwroot + '/mod/quiz/accessrule/edproctoring/amd/src/vendor/models';
    const DETECT_INTERVAL_MS = 2000;    // Run detection every 2 seconds.
    const MIN_FACE_SCORE     = 0.3;     // Minimum detection confidence (0.5 caused false FACE_ABSENT in dim light).
    const DETECT_INPUT_SIZE  = 512;     // Larger input = better accuracy for small/angled faces.
    const LOW_BRIGHTNESS_THRESHOLD = 0.3;

    let intervalId    = null;
    let graceTimer    = null;
    let videoEl       = null;
    let canvas        = null;
    let callbacks     = {};
    let gracePeriodMs = 5000;
    let missCount     = 0;      // Consecutive detection cycles with no face.

    /**
     * Compute average brightness of current video frame.
     * Returns 0.0–1.0. Below 0.3 = LOW_LIGHT.
     *
     * @param {HTMLVideoElement} video
     * @returns {number}
     */
    function getBrightness(video) {
        const offscreen = document.createElement('canvas');
        offscreen.width  = 80;  // Small sample — fast.
        offscreen.height = 60;
        const ctx  = offscreen.getContext('2d');
        ctx.drawImage(video, 0, 0, 80, 60);
        const data = ctx.getImageData(0, 0, 80, 60).data;
        let sum    = 0;
        for (let i = 0; i < data.length; i += 4) {
            // Perceived brightness formula (0.299R + 0.587G + 0.114B).
            sum += 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
        }
        return sum / (data.length / 4) / 255;
    }

    /**
     * Run one detection cycle.
     */
    async function detect() {
        if (!videoEl || videoEl.readyState < 2) {
            return;
        }

        const brightness = getBrightness(videoEl);

        // Draw frame to canvas for face-api.
        const ctx = canvas.getContext('2d');
        canvas.width  = videoEl.videoWidth  || 640;
        canvas.height = videoEl.videoHeight || 480;
        ctx.drawImage(videoEl, 0, 0, canvas.width, canvas.height);

        let detections;
        try {
            detections = await faceapi.detectAllFaces(
                canvas,
                new faceapi.TinyFaceDetectorOptions({inputSize: DETECT_INPUT_SIZE, scoreThreshold: MIN_FACE_SCORE})
            );
        } catch (e) {
            console.warn('EDP face-detector: detection error', e);
            return;
        }

        const faceCount = detections.length;

        if (faceCount === 0) {
            missCount++;
            // Require 2 consecutive misses before starting the grace countdown —
            // a single marginal frame must not trigger FACE_ABSENT.
            if (missCount >= 2 && !graceTimer) {
                graceTimer = setTimeout(() => {
                    graceTimer = null;
                    if (callbacks.onFaceAbsent) {
                        callbacks.onFaceAbsent();
                    }
                }, gracePeriodMs);
            }
        } else {
            // Face(s) found — reset misses and cancel grace timer.
            missCount = 0;
            if (graceTimer) {
                clearTimeout(graceTimer);
                graceTimer = null;
            }

            if (faceCount > 1 && callbacks.onMultiFace) {
                callbacks.onMultiFace(faceCount);
            }
        }

        // Low light check — fire independently.
        if (brightness < LOW_BRIGHTNESS_THRESHOLD && faceCount === 0
                && callbacks.onLowLight) {
            callbacks.onLowLight(brightness);
        }
    }

    /**
     * Initialise face detection on a video element.
     *
     * @param {HTMLVideoElement} video
     * @param {Object} opts
     * @param {number} opts.gracePeriod     seconds
     * @param {Function} opts.onFaceAbsent  called after grace period with no face
     * @param {Function} opts.onMultiFace   called with face count > 1
     * @param {Function} opts.onLowLight    called when brightness is too low
     */
    async function init(video, opts) {
        videoEl       = video;
        callbacks     = opts;
        gracePeriodMs = (opts.gracePeriod || 5) * 1000;

        canvas = document.createElement('canvas');

        // Load TinyFaceDetector model (weights bundled in amd/src/vendor/models/).
        try {
            await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
            console.log('EDP face-detector: model loaded');
        } catch (e) {
            console.error('EDP face-detector: failed to load model', e);
            return;
        }

        // Start periodic detection.
        intervalId = setInterval(detect, DETECT_INTERVAL_MS);
    }

    /**
     * Stop detection and clear timers.
     */
    function stop() {
        if (intervalId) {
            clearInterval(intervalId);
            intervalId = null;
        }
        if (graceTimer) {
            clearTimeout(graceTimer);
            graceTimer = null;
        }
    }

    return {init, stop};
});
