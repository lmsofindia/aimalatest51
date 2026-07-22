// This file is part of Moodle - http://moodle.org/
//
// quizaccess_edproctoring — In-quiz camera overlay UI.
// Shows live video thumbnail, recording dot, violation count, and toast alerts.
//
// @package   quizaccess_edproctoring
// @copyright 2025 EDZLMS
// @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

define([], function() {

    'use strict';

    const TOAST_DURATION_MS = 5000;

    let overlayEl        = null;
    let warningCountEl   = null;
    let reenterBtn       = null;
    let toastContainer   = null;

    /**
     * Create and inject the overlay HTML into the quiz page.
     *
     * @param {HTMLVideoElement} video
     */
    function init(video) {
        // Build overlay container.
        overlayEl = document.createElement('div');
        overlayEl.id = 'edp-camera-overlay';
        overlayEl.setAttribute('aria-live', 'polite');
        overlayEl.setAttribute('aria-label', 'Proctoring camera overlay');
        overlayEl.innerHTML = `
            <div id="edp-video-wrapper">
                <div id="edp-rec-dot" title="Recording"></div>
            </div>
            <div id="edp-overlay-footer">
                <span id="edp-warning-label">⚠</span>
                <span id="edp-warning-count">0</span>
            </div>
            <button id="edp-reenter-fs" style="display:none" type="button">
                ↗ Re-enter fullscreen
            </button>
        `;

        // Inject styles.
        const style = document.createElement('style');
        style.textContent = `
            #edp-camera-overlay {
                position: fixed;
                top: 12px;
                right: 12px;
                width: 148px;
                background: rgba(0,0,0,0.75);
                border-radius: 8px;
                padding: 4px;
                z-index: 9999;
                font-size: 12px;
                color: #fff;
                user-select: none;
            }
            #edp-video-wrapper {
                position: relative;
                width: 140px;
                height: 105px;
                overflow: hidden;
                border-radius: 4px;
                background: #000;
            }
            #edp-video-wrapper video {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            #edp-rec-dot {
                position: absolute;
                top: 6px;
                left: 6px;
                width: 10px;
                height: 10px;
                background: #e53935;
                border-radius: 50%;
                animation: edp-blink 1.5s infinite;
            }
            @keyframes edp-blink {
                0%,100% { opacity: 1; }
                50%      { opacity: 0.2; }
            }
            #edp-overlay-footer {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 4px 0 2px;
                gap: 4px;
            }
            #edp-warning-count {
                font-weight: bold;
                font-size: 13px;
            }
            #edp-reenter-fs {
                width: 100%;
                margin-top: 4px;
                background: #1976d2;
                color: #fff;
                border: none;
                border-radius: 4px;
                padding: 4px;
                cursor: pointer;
                font-size: 11px;
            }
            #edp-toast-container {
                position: fixed;
                top: 60px;
                right: 12px;
                width: 300px;
                z-index: 10000;
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            .edp-toast {
                background: #b71c1c;
                color: #fff;
                border-radius: 6px;
                padding: 10px 14px;
                font-size: 13px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.4);
                animation: edp-slideIn 0.2s ease;
            }
            .edp-toast.warning  { background: #e65100; }
            .edp-toast.info     { background: #1565c0; }
            @keyframes edp-slideIn {
                from { opacity: 0; transform: translateX(20px); }
                to   { opacity: 1; transform: translateX(0); }
            }
        `;
        document.head.appendChild(style);

        // Insert video into wrapper.
        const wrapper = overlayEl.querySelector('#edp-video-wrapper');
        wrapper.insertBefore(video, wrapper.firstChild);

        document.body.appendChild(overlayEl);

        // Toast container (separate from overlay — wider).
        toastContainer = document.createElement('div');
        toastContainer.id = 'edp-toast-container';
        document.body.appendChild(toastContainer);

        warningCountEl = overlayEl.querySelector('#edp-warning-count');
        reenterBtn     = overlayEl.querySelector('#edp-reenter-fs');
    }

    /**
     * Update the warning count badge.
     *
     * @param {number} count
     */
    function setWarningCount(count) {
        if (warningCountEl) {
            warningCountEl.textContent = count;
            if (count > 0) {
                warningCountEl.style.color = '#ffcdd2';
            }
        }
    }

    /**
     * Show a toast notification for a violation.
     *
     * @param {string} message
     * @param {string} severity 'critical'|'warning'|'info'
     */
    function showToast(message, severity = 'warning') {
        if (!toastContainer) {
            return;
        }
        const toast = document.createElement('div');
        toast.className = `edp-toast ${severity === 'critical' ? '' : severity}`;
        toast.textContent = message;
        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.4s';
            setTimeout(() => toast.remove(), 400);
        }, TOAST_DURATION_MS);
    }

    /**
     * Show auto-submit warning toast.
     */
    function showAutoSubmitWarning() {
        showToast(
            '⚠ Maximum violations reached. Your quiz will be submitted in 3 seconds.',
            'critical'
        );
    }

    /**
     * Show a camera error message in the overlay.
     *
     * @param {string} message
     */
    function showCameraError(message) {
        if (!overlayEl) {
            return;
        }
        const wrapper = overlayEl.querySelector('#edp-video-wrapper');
        wrapper.innerHTML = `<div style="color:#ffcdd2;font-size:11px;padding:8px;text-align:center">${message}</div>`;
        showToast('🚫 ' + message, 'critical');
    }

    /**
     * Return the re-enter fullscreen button element for external wiring.
     *
     * @returns {HTMLButtonElement|null}
     */
    function getReenterFullscreenButton() {
        return reenterBtn;
    }

    return {init, setWarningCount, showToast, showAutoSubmitWarning, showCameraError, getReenterFullscreenButton};
});
