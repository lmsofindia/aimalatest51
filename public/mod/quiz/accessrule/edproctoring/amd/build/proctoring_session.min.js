// This file is part of Moodle - http://moodle.org/
//
// quizaccess_edproctoring — Main session orchestrator AMD module.
// Initialised on the quiz attempt page. Coordinates all sub-modules.
// Face detection is loaded dynamically only when enableFaceDetect=true.
//
// @package   quizaccess_edproctoring
// @copyright 2025 EDZLMS
// @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

define([
    'core/ajax',
    'core/notification',
    'quizaccess_edproctoring/capture_manager',
    'quizaccess_edproctoring/tab_monitor',
    'quizaccess_edproctoring/violation_reporter',
    'quizaccess_edproctoring/camera_overlay',
    'quizaccess_edproctoring/copy_paste_monitor',
    'quizaccess_edproctoring/warning_modal',
    'quizaccess_edproctoring/idle_monitor',
], function(Ajax, Notification, CaptureManager,
            TabMonitor, ViolationReporter, CameraOverlay, CopyPasteMonitor,
            WarningModal, IdleMonitor) {

    'use strict';

    let config       = {};
    let videoEl      = null;
    let stream       = null;
    let attemptStart = 0;

    function elapsedSeconds() {
        return Math.floor((Date.now() - attemptStart) / 1000);
    }

    function getCurrentPage() {
        const pageInput = document.querySelector('input[name="page"]');
        return pageInput ? parseInt(pageInput.value, 10) : 0;
    }

    function cleanup() {
        if (stream) {
            stream.getTracks().forEach(t => t.stop());
            stream = null;
        }
        CaptureManager.stop();
        TabMonitor.stop();
        CopyPasteMonitor.stop();
        IdleMonitor.stop();
    }

    function onViolation(type, details) {
        details = details || {};
        const elapsed = elapsedSeconds();
        const page    = getCurrentPage();

        const criticalTypes = ['FACE_ABSENT', 'MULTIPLE_FACES', 'CAMERA_BLOCKED'];
        if (criticalTypes.includes(type)) {
            // Extra evidence burst for critical violations.
            CaptureManager.triggerBurst(elapsed, page, true);
        }

        // Show the student a warning modal for every detected violation.
        WarningModal.show(type);

        // Capture ONE frame for every violation and link it to the violation
        // record (snap_id), so the report can colour images by severity.
        const frame = CaptureManager.captureFrame();
        const snapPromise = frame
            ? CaptureManager.sendSnapshot(frame, 'burst', true)
            : Promise.resolve({snapid: 0});

        snapPromise.then(function(snap) {
            return ViolationReporter.log(config.sessionId, type,
                JSON.stringify(details), page, elapsed, snap.snapid || 0);
        })
            .then(function(response) {
                CameraOverlay.setWarningCount(response.warningcount);
                if (response.autosubmit) {
                    CameraOverlay.showAutoSubmitWarning();
                    setTimeout(function() {
                        const btn = document.querySelector('input[name="finishattempt"]')
                            || document.querySelector('.submitbtns input[type="submit"]');
                        if (btn) { btn.click(); }
                    }, 3000);
                }
            }).catch(function(err) {
                console.error('EDP: violation log failed', err);
            });
    }

    function onCameraBlocked() {
        onViolation('CAMERA_BLOCKED', {reason: 'track_ended'});
        CameraOverlay.showCameraError('Camera disconnected. Please re-enable your camera.');
        CaptureManager.stop();
    }

    function startFaceDetection() {
        // Dynamically load face_detector only when needed.
        // Wraps in try/catch so a missing vendor file never kills the session.
        require(['quizaccess_edproctoring/face_detector'], function(FaceDetector) {
            FaceDetector.init(videoEl, {
                gracePeriod:  config.gracePeriod,
                onFaceAbsent: function() { onViolation('FACE_ABSENT',    {grace: config.gracePeriod}); },
                onMultiFace:  function(n){ onViolation('MULTIPLE_FACES', {face_count: n}); },
                onLowLight:   function() { onViolation('LOW_LIGHT', {}); },
            });
        }, function(err) {
            // face-api.js vendor not available yet (Phase 2). Silently skip.
            console.info('EDP: face detection unavailable (Phase 2 feature)', err.message);
        });
    }

    function init(cfg) {
        config       = cfg;
        attemptStart = Date.now();

        WarningModal.init();

        console.log('EDP: session initialized', config);

        navigator.mediaDevices.getUserMedia({
            video: {width: {ideal: 640}, height: {ideal: 480}},
            audio: false,
        }).then(function(mediaStream) {
            stream  = mediaStream;
            videoEl = document.createElement('video');
            videoEl.srcObject   = stream;
            videoEl.autoplay    = true;
            videoEl.muted       = true;
            videoEl.playsInline = true;

            stream.getVideoTracks().forEach(function(track) {
                track.addEventListener('ended', onCameraBlocked);
                track.addEventListener('mute',  onCameraBlocked);
            });

            CameraOverlay.init(videoEl);

            if (config.enableFaceDetect) {
                startFaceDetection();
            }

            CaptureManager.init(videoEl, {
                sessionId:       config.sessionId,
                captureInterval: config.captureInterval,
                getElapsed:      elapsedSeconds,
                getPage:         getCurrentPage,
            });

            if (config.detectTabs) {
                TabMonitor.init({
                    requireFullscreen:    config.requireFullscreen,
                    onTabSwitch:          function() { onViolation('TAB_SWITCH', {}); },
                    onFullscreenExit:     function() { onViolation('FULLSCREEN_EXIT', {}); },
                    overlayReenterButton: CameraOverlay.getReenterFullscreenButton(),
                });
                if (config.requireFullscreen) {
                    TabMonitor.requestFullscreen();
                }
            }

            CopyPasteMonitor.init({
                onCopyPaste: function(action) { onViolation('COPY_PASTE', {action: action}); },
            });

            IdleMonitor.init({
                idleSeconds: 30,
                onIdle: function() { onViolation('IDLE', {seconds: 30}); },
            });

        }).catch(function(err) {
            console.error('EDP: camera access denied', err);
            onViolation('CAMERA_BLOCKED', {reason: 'access_denied', error: err.name});
        });

        window.addEventListener('beforeunload', cleanup);
        const form = document.querySelector('#responseform');
        if (form) { form.addEventListener('submit', cleanup); }
    }

    return {init: init};
});
