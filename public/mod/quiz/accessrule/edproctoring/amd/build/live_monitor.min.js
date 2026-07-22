// This file is part of Moodle - http://moodle.org/
//
// quizaccess_edproctoring — Live monitor wall.
// Polls the get_live_sessions web service and renders a grid of active
// exam takers, sorted suspicious-first. Pauses while the tab is hidden.
//
// @package   quizaccess_edproctoring
// @copyright 2025 EDZLMS
// @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

define(['core/ajax', 'core/str'], function(Ajax, Str) {

    'use strict';

    var config = {cmid: 0, refresh: 9};
    var timer = null;
    var strings = {
        lost: 'Connection lost',
        noimage: 'No image yet',
        updated: 'Updated',
        error: 'Update failed — retrying'
    };

    /**
     * HTML-escape a value.
     * @param {*} value
     * @return {string}
     */
    function esc(value) {
        var div = document.createElement('div');
        div.textContent = (value === null || value === undefined) ? '' : String(value);
        // Escape quotes too: values are interpolated into double-quoted HTML attributes
        // (e.g. title="..."), and innerHTML alone does not encode " or '.
        return div.innerHTML.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    /**
     * Format elapsed seconds as "1h 4m" or "12m 30s".
     * @param {number} sec
     * @return {string}
     */
    function fmtElapsed(sec) {
        sec = Math.max(0, sec | 0);
        var h = Math.floor(sec / 3600);
        var m = Math.floor((sec % 3600) / 60);
        var s = sec % 60;
        if (h > 0) {
            return h + 'h ' + m + 'm';
        }
        return m + 'm ' + s + 's';
    }

    /**
     * CSS class for a trust band.
     * @param {string} band
     * @return {string}
     */
    function bandClass(band) {
        if (band === 'low_risk') {
            return 'edp-band-low';
        }
        if (band === 'review') {
            return 'edp-band-review';
        }
        return 'edp-band-high';
    }

    /**
     * Sort comparator: most critical first, then lowest trust, then most violations.
     * @param {Object} a
     * @param {Object} b
     * @return {number}
     */
    function sortTiles(a, b) {
        if (b.critical_violations !== a.critical_violations) {
            return b.critical_violations - a.critical_violations;
        }
        if (a.trust !== b.trust) {
            return a.trust - b.trust;
        }
        return b.total_violations - a.total_violations;
    }

    /**
     * Build the HTML for a single tile.
     * @param {Object} t
     * @return {string}
     */
    function tileHtml(t) {
        var detailUrl = M.cfg.wwwroot +
            '/mod/quiz/accessrule/edproctoring/report/attempt_detail.php?sessionid=' + t.sessionid;

        var img = t.thumburl ?
            '<img src="' + esc(t.thumburl) + '" alt="" class="edp-live-thumb" loading="lazy">' :
            '<div class="edp-live-thumb edp-live-noimg"><span>' + esc(strings.noimage) + '</span></div>';

        var staleBadge = t.stale ?
            '<span class="edp-live-stale">' + esc(strings.lost) + '</span>' : '';

        var sub = config.cmid ?
            esc(t.courseshort) :
            esc(t.courseshort) + ' &middot; ' + esc(t.quizname);

        var lastViol = t.lastviolation ?
            '<div class="edp-live-lastviol">' + esc(t.lastviolation) + '</div>' : '';

        return '<a class="edp-live-tile ' + bandClass(t.band) + (t.stale ? ' edp-live-tilestale' : '') + '"' +
            ' href="' + detailUrl + '" target="_blank" rel="noopener"' +
            ' title="' + esc(t.fullname) + '">' +
            '<div class="edp-live-imgwrap">' + img + staleBadge +
                '<span class="edp-live-trust">' + Math.round(t.trust) + '</span>' +
            '</div>' +
            '<div class="edp-live-meta">' +
                '<div class="edp-live-name">' + esc(t.fullname) + '</div>' +
                '<div class="edp-live-sub">' + sub + '</div>' +
                '<div class="edp-live-stats">' +
                    '<span class="edp-live-crit">' + t.critical_violations + ' crit</span>' +
                    '<span class="edp-live-warn">' + t.warning_violations + ' warn</span>' +
                    '<span class="edp-live-elapsed">' + esc(fmtElapsed(t.elapsed)) + '</span>' +
                '</div>' + lastViol +
            '</div>' +
        '</a>';
    }

    /**
     * Render the full grid from a session list.
     * @param {Array} sessions
     */
    function render(sessions) {
        var grid = document.getElementById('edp-live-grid');
        var empty = document.getElementById('edp-live-empty');
        var count = document.getElementById('edp-live-count');
        if (!grid) {
            return;
        }
        sessions = sessions || [];
        sessions.sort(sortTiles);

        if (count) {
            count.textContent = sessions.length;
        }
        if (!sessions.length) {
            grid.innerHTML = '';
            if (empty) {
                empty.hidden = false;
            }
            return;
        }
        if (empty) {
            empty.hidden = true;
        }
        var html = '';
        for (var i = 0; i < sessions.length; i++) {
            html += tileHtml(sessions[i]);
        }
        grid.innerHTML = html;
    }

    /**
     * Update the "last updated" label.
     * @param {string} text
     */
    function setUpdated(text) {
        var el = document.getElementById('edp-live-updated');
        if (el) {
            el.textContent = text;
        }
    }

    /**
     * Fetch the latest active sessions and render them.
     */
    function poll() {
        Ajax.call([{
            methodname: 'quizaccess_edproctoring_get_live_sessions',
            args: {cmid: config.cmid}
        }])[0].done(function(resp) {
            render(resp.sessions || []);
            setUpdated(strings.updated + ' ' + new Date().toLocaleTimeString());
        }).fail(function() {
            setUpdated(strings.error);
        });
    }

    /**
     * (Re)start the polling timer.
     */
    function schedule() {
        if (timer) {
            window.clearInterval(timer);
        }
        var ms = Math.max(3, config.refresh) * 1000;
        timer = window.setInterval(function() {
            if (document.hidden) {
                return;
            }
            poll();
        }, ms);
    }

    return {
        /**
         * Initialise the live monitor.
         * @param {Object} cfg {cmid, refresh}
         */
        init: function(cfg) {
            cfg = cfg || {};
            config.cmid = parseInt(cfg.cmid, 10) || 0;
            config.refresh = parseInt(cfg.refresh, 10) || 9;

            Str.get_strings([
                {key: 'livemonitor_lost', component: 'quizaccess_edproctoring'},
                {key: 'livemonitor_noimage', component: 'quizaccess_edproctoring'},
                {key: 'livemonitor_updated', component: 'quizaccess_edproctoring'},
                {key: 'livemonitor_error', component: 'quizaccess_edproctoring'}
            ]).done(function(s) {
                strings.lost = s[0];
                strings.noimage = s[1];
                strings.updated = s[2];
                strings.error = s[3];
            }).always(function() {
                poll();
                schedule();
            });

            var btn = document.getElementById('edp-live-refresh');
            if (btn) {
                btn.addEventListener('click', poll);
            }
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    poll();
                }
            });
        }
    };
});
