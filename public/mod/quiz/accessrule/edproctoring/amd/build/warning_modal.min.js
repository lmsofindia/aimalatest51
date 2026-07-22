// This file is part of Moodle - http://moodle.org/
//
// quizaccess_edproctoring — Student-facing warning modal.
// Shows a blocking warning (face not visible, tab switch, idle, etc.)
// until the student acknowledges it.
//
// @package   quizaccess_edproctoring
// @copyright 2025 EDZLMS
// @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

define(['core/str'], function(Str) {

    'use strict';

    const TYPES = [
        'FACE_ABSENT', 'MULTIPLE_FACES', 'TAB_SWITCH', 'FULLSCREEN_EXIT',
        'COPY_PASTE', 'LOW_LIGHT', 'CAMERA_BLOCKED', 'IDLE',
    ];

    let strings = {};   // key => translated string
    let overlay = null;
    let msgEl   = null;

    /**
     * Build the modal DOM once (hidden by default).
     */
    function ensureDom() {
        if (overlay) {
            return;
        }
        overlay = document.createElement('div');
        overlay.id = 'edp-warning-modal';
        overlay.setAttribute('role', 'alertdialog');
        overlay.setAttribute('aria-live', 'assertive');
        overlay.style.cssText = [
            'position:fixed', 'inset:0', 'display:none',
            'align-items:center', 'justify-content:center',
            'background:rgba(0,0,0,.55)', 'z-index:100000',
        ].join(';');

        const card = document.createElement('div');
        card.style.cssText = [
            'background:#fff', 'color:#1d2125', 'max-width:420px', 'width:92%',
            'border-radius:12px', 'padding:28px 24px', 'text-align:center',
            'box-shadow:0 12px 40px rgba(0,0,0,.35)',
            'font-family:inherit',
        ].join(';');

        const icon = document.createElement('div');
        icon.textContent = '⚠️';
        icon.style.cssText = 'font-size:44px;line-height:1;margin-bottom:10px;';

        const title = document.createElement('div');
        title.id = 'edp-warning-title';
        title.textContent = strings.modal_title || 'Proctoring warning';
        title.style.cssText = 'font-size:1.25rem;font-weight:700;margin-bottom:8px;';

        msgEl = document.createElement('div');
        msgEl.id = 'edp-warning-msg';
        msgEl.style.cssText = 'font-size:1rem;margin-bottom:20px;';

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = strings.modal_dismiss || 'I understand';
        btn.style.cssText = [
            'background:#d63939', 'color:#fff', 'border:0', 'cursor:pointer',
            'padding:10px 28px', 'border-radius:8px', 'font-size:1rem', 'font-weight:600',
        ].join(';');
        btn.addEventListener('click', hide);

        card.appendChild(icon);
        card.appendChild(title);
        card.appendChild(msgEl);
        card.appendChild(btn);
        overlay.appendChild(card);
        document.body.appendChild(overlay);
    }

    /**
     * Show the modal for a violation type.
     *
     * @param {string} type violation type e.g. 'FACE_ABSENT'
     */
    function show(type) {
        ensureDom();
        msgEl.textContent = strings['warnmsg_' + type] || type;
        overlay.style.display = 'flex';
    }

    /**
     * Hide the modal.
     */
    function hide() {
        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    /**
     * Preload all warning strings (async, fire and forget).
     */
    function init() {
        const requests = [
            {key: 'modal_title',   component: 'quizaccess_edproctoring'},
            {key: 'modal_dismiss', component: 'quizaccess_edproctoring'},
        ];
        TYPES.forEach(function(t) {
            requests.push({key: 'warnmsg_' + t, component: 'quizaccess_edproctoring'});
        });

        Str.get_strings(requests).then(function(results) {
            requests.forEach(function(req, i) {
                strings[req.key] = results[i];
            });
            return null;
        }).catch(function() {
            // Fall back to hardcoded type names — never block proctoring on strings.
        });
    }

    return {init, show, hide};
});
