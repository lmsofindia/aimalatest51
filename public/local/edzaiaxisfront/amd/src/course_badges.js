// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Course-page AI badges.
 *
 * Loaded (via classes/hook/output_callbacks.php::before_footer) only on course
 * view pages. Fetches the cmids in this course that have learner-visible AI
 * content and drops a small "AI" pill on each matching activity. The pill links
 * into that activity's page with a #ai hash so the AI Learning Assistant panel
 * auto-opens on arrival (see student_panel.js).
 *
 * @module     local_edzaiaxisfront/course_badges
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Repository from 'local_edzaiaxisfront/repository';

const SPARK =
    '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" ' +
    'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
    '<path d="M12 3l1.9 4.1L18 9l-4.1 1.9L12 15l-1.9-4.1L6 9l4.1-1.9z"/></svg>';

const findActivityNode = (cmid) =>
    document.getElementById('module-' + cmid) ||
    document.querySelector('li.activity[data-id="' + cmid + '"]') ||
    document.querySelector('[data-cmid="' + cmid + '"]');

const activityHref = (node) => {
    const a = node.querySelector(
        'a.aalink, a.stretched-link, .activityname a, a[href*="view.php?id="]');
    return a ? a.getAttribute('href') : null;
};

const decorate = (cmid) => {
    const node = findActivityNode(cmid);
    if (!node || node.querySelector('.edzai-ai-badge')) {
        return;
    }
    const href = activityHref(node);

    const badge = document.createElement('a');
    badge.className = 'edzai-ai-badge';
    badge.href = (href || '#') + '#ai';
    badge.title = 'AI Learning Assistant available for this activity';
    badge.setAttribute('aria-label', 'Open the AI Learning Assistant');
    badge.innerHTML = SPARK + '<span>AI</span>';

    // Attach next to the activity name, avoiding nested <a> (invalid HTML).
    let target = node.querySelector(
        '.activityname, .activity-instance, .activity-item') || node;
    if (target.tagName === 'A') {
        target = target.parentElement || node;
    }
    target.appendChild(badge);
};

export const init = async (courseid) => {
    let cmids = [];
    try {
        const res = await Repository.getCourseAiCmids(courseid);
        cmids = (res && res.cmids) || [];
    } catch (e) {
        return;
    }
    cmids.forEach(decorate);
};
