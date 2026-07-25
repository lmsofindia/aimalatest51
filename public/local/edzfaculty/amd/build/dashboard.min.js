// This file is part of the local_edzfaculty plugin for Moodle.
//
// Faculty dashboard interactivity: tabs, course focus, engagement filter,
// My Courses search, at-risk student modal + nudge, and the course report page.

/**
 * @module local_edzfaculty/dashboard
 * @copyright 2026 EDZLEARN
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    'use strict';

    var data = {};

    /**
     * @param {number} n
     * @return {string}
     */
    function num(n) {
        return String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /**
     * @param {number} t
     * @return {string}
     */
    function trendText(t) {
        if (Math.abs(t) < 0.5) {
            return '— ' + (data.strings.flat || 'flat');
        }
        return (t > 0 ? '▲ ' : '▼ ') + Math.abs(t) + '%';
    }

    /**
     * @param {number} t
     * @return {string}
     */
    function trendClass(t) {
        if (Math.abs(t) < 0.5) {
            return 'flat';
        }
        return t > 0 ? 'up' : 'down';
    }

    /**
     * @param {string} s
     * @return {string}
     */
    function esc(s) {
        var div = document.createElement('div');
        div.textContent = s == null ? '' : String(s);
        return div.innerHTML;
    }

    /**
     * @param {HTMLElement} root
     */
    function wireTabs(root) {
        root.querySelectorAll('.edzf-tab').forEach(function(tab) {
            tab.addEventListener('click', function() {
                var key = tab.getAttribute('data-tab');
                root.querySelectorAll('.edzf-tab').forEach(function(t) {
                    t.classList.toggle('active', t === tab);
                });
                root.querySelectorAll('.edzf-pane').forEach(function(p) {
                    p.classList.toggle('active', p.getAttribute('data-pane') === key);
                });
            });
        });
    }

    /**
     * @param {HTMLElement} root
     * @param {number|string} cid
     */
    function applyFocus(root, cid) {
        var f = (cid && data.focusmap[cid]) ? data.focusmap[cid] : data.focusall;
        var set = function(region, val) {
            var el = root.querySelector('[data-region="' + region + '"]');
            if (el) {
                el.textContent = val;
            }
        };
        set('cf-live', f.live);
        set('cf-assign', f.assign);
        set('cf-disc', f.disc);
        set('cf-quiz', f.quiz);
        set('cf-ctx', f.ctx);
        set('cf-qalbl', f.label);
        if (f.actions) {
            Object.keys(f.actions).forEach(function(k) {
                var a = root.querySelector('.edzf-qa[data-qa="' + k + '"]');
                if (a) {
                    a.setAttribute('href', f.actions[k]);
                }
            });
        }
    }

    /**
     * @param {HTMLElement} root
     */
    function wireCourseFocus(root) {
        var sel = root.querySelector('#edzf-course');
        if (sel) {
            sel.addEventListener('change', function() {
                applyFocus(root, sel.value);
            });
        }
    }

    /**
     * @param {number|string} cid
     * @param {object} e
     * @return {string}
     */
    function insight(cid, e) {
        if (!cid && data.chart && data.chart.labels.length) {
            var mini = 0;
            data.chart.values.forEach(function(v, i) {
                if (v < data.chart.values[mini]) {
                    mini = i;
                }
            });
            return data.chart.labels[mini] + ' is currently your lowest section at ' +
                data.chart.values[mini] + '%. Consider a revision session.';
        }
        return 'Active students ' + Math.round(e.active.value) + '%, average score ' +
            Math.round(e.score.value) + '% ' + trendText(e.score.trend) + '.';
    }

    /**
     * @param {HTMLElement} root
     * @param {number|string} cid
     */
    function applyEngagement(root, cid) {
        var e = (cid && data.engagemap[cid]) ? data.engagemap[cid] : data.engageall;
        var suffix = {active: '%', views: '', assess: '', score: '%'};
        ['active', 'views', 'assess', 'score'].forEach(function(k) {
            var v = root.querySelector('[data-eng-key="' + k + '"]');
            var t = root.querySelector('[data-eng-trend="' + k + '"]');
            if (v) {
                v.textContent = suffix[k] === '%' ? Math.round(e[k].value) + '%' : num(e[k].value);
            }
            if (t) {
                t.textContent = trendText(e[k].trend);
                t.className = 'edzf-et ' + trendClass(e[k].trend);
            }
        });
        var ai = root.querySelector('[data-region="eng-ai"]');
        if (ai && data.showai) {
            ai.innerHTML = '<b>' + data.strings.aiinsight + ':</b> ' + insight(cid, e);
        }
    }

    /**
     * @param {HTMLElement} root
     */
    function wireEngagement(root) {
        var sel = root.querySelector('#edzf-egsel');
        if (sel) {
            sel.addEventListener('change', function() {
                applyEngagement(root, sel.value);
            });
        }
    }

    /**
     * My Courses live search.
     * @param {HTMLElement} root
     */
    function wireCourseSearch(root) {
        var input = root.querySelector('#edzf-csrch');
        if (!input) {
            return;
        }
        input.addEventListener('input', function() {
            var q = input.value.toLowerCase().trim();
            var shown = 0;
            root.querySelectorAll('.edzf-course').forEach(function(c) {
                var hit = c.getAttribute('data-search').toLowerCase().indexOf(q) > -1;
                c.style.display = hit ? '' : 'none';
                if (hit) {
                    shown++;
                }
            });
            var empty = root.querySelector('[data-region="cempty"]');
            if (empty) {
                empty.style.display = shown ? 'none' : 'block';
            }
        });
    }

    /**
     * @param {HTMLElement} root
     */
    function wireStudents(root) {
        root.querySelectorAll('[data-action="student"]').forEach(function(row) {
            row.addEventListener('click', function() {
                openStudent(row.dataset);
            });
        });
    }

    /**
     * Build and show the student-360 modal (with nudge box).
     * @param {DOMStringMap} d
     */
    function openStudent(d) {
        var s = data.strings || {};
        var reasons = [];
        try {
            reasons = JSON.parse(d.reasons || '[]');
        } catch (e) {
            reasons = [];
        }
        var overlay = document.createElement('div');
        overlay.className = 'edzf-overlay';
        var reasonhtml = reasons.map(function(r) {
            return '<div class="edzf-mflag">▲ ' + esc(r) + '</div>';
        }).join('');
        overlay.innerHTML =
            '<div class="edzf-modal" role="dialog" aria-modal="true" aria-label="' + esc(d.name) + '">' +
              '<div class="edzf-modal-h"><div class="edzf-mav">' + esc(d.initials) + '</div>' +
                '<div><div class="edzf-mname">' + esc(d.name) + '</div><div class="edzf-muted">' + esc(d.section) + '</div></div>' +
                '<button class="edzf-mclose" aria-label="Close" type="button">×</button></div>' +
              '<div class="edzf-m360">' +
                '<div class="edzf-m"><b>' + esc(d.attendance) + '%</b><span>' + esc(s.attendance) + '</span></div>' +
                '<div class="edzf-m"><b>' + esc(d.avgscore) + '</b><span>' + esc(s.avgscore) + '</span></div>' +
                '<div class="edzf-m"><b>' + esc(d.lastaccess) + '</b><span>' + esc(s.lastlogin) + '</span></div>' +
                '<div class="edzf-m"><b>' + esc(d.missed) + '</b><span>' + esc(s.missed) + '</span></div>' +
                '<div class="edzf-m"><b>' + esc(d.forumposts) + '</b><span>' + esc(s.forumposts) + '</span></div>' +
                '<div class="edzf-m"><b>' + esc(d.trend) + '%</b><span>' + esc(s.scoretrend) + '</span></div>' +
              '</div>' +
              '<div class="edzf-msec"><h4>' + esc(s.whyflagged) + '</h4>' + reasonhtml +
                '<textarea class="edzf-nudge" rows="3" placeholder="' + esc(s.nudgeph) + '"></textarea>' +
                '<div class="edzf-mbtns">' +
                  '<button class="edzf-btn" type="button" data-action="nudge-send">' + esc(s.sendnudge) + '</button>' +
                  '<a class="edzf-btn ghost" href="' + esc(d.profileurl) + '">' + esc(s.fullrecord) + '</a>' +
                '</div>' +
              '</div>' +
            '</div>';
        document.body.appendChild(overlay);

        var onkey = function(e) {
            if (e.key === 'Escape') {
                close();
            }
        };
        var close = function() {
            document.removeEventListener('keydown', onkey);
            overlay.remove();
        };
        document.addEventListener('keydown', onkey);
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                close();
            }
        });
        overlay.querySelector('.edzf-mclose').addEventListener('click', close);
        overlay.querySelector('.edzf-mclose').focus();
        overlay.querySelector('[data-action="nudge-send"]').addEventListener('click', function(ev) {
            sendNudge(ev.currentTarget, d.userid, d.courseid, overlay.querySelector('.edzf-nudge').value, close);
        });
    }

    /**
     * @param {HTMLElement} btn
     * @param {string} userid
     * @param {string} courseid
     * @param {string} body
     * @param {function} onsent
     */
    function sendNudge(btn, userid, courseid, body, onsent) {
        btn.disabled = true;
        Ajax.call([{
            methodname: 'local_edzfaculty_send_nudge',
            args: {studentid: parseInt(userid, 10), courseid: parseInt(courseid, 10), message: body}
        }])[0].then(function(res) {
            Notification.addNotification({message: res.message, type: res.sent ? 'success' : 'warning'});
            if (res.sent && onsent) {
                onsent();
            }
            return res;
        }).catch(Notification.exception).always(function() {
            btn.disabled = false;
        });
    }

    return {
        /**
         * Dashboard entry point.
         */
        init: function() {
            var root = document.querySelector('[data-region="edzfaculty"]');
            if (!root) {
                return;
            }
            var el = root.querySelector('[data-region="edzf-data"]');
            try {
                data = JSON.parse(el.textContent);
            } catch (e) {
                data = {focusall: {}, focusmap: {}, engageall: {}, engagemap: {}, chart: {labels: [], values: []}, strings: {}};
            }
            wireTabs(root);
            wireCourseFocus(root);
            wireEngagement(root);
            wireCourseSearch(root);
            wireStudents(root);
            applyEngagement(root, 0);
        },

        /**
         * Overview table: clickable rows.
         */
        initOverview: function() {
            document.querySelectorAll('.edzf-trow[data-href]').forEach(function(tr) {
                tr.addEventListener('click', function(e) {
                    if (e.target.closest('a')) {
                        return;
                    }
                    window.location.href = tr.getAttribute('data-href');
                });
            });
        },

        /**
         * Course report page: roster search + nudge buttons.
         */
        initReport: function() {
            var root = document.querySelector('[data-region="edzf-report"]');
            if (!root) {
                return;
            }
            var el = root.querySelector('[data-region="edzf-data"]');
            try {
                data = JSON.parse(el.textContent);
            } catch (e) {
                data = {strings: {}};
            }
            var input = root.querySelector('[data-action="roster-search"]');
            if (input) {
                input.addEventListener('input', function() {
                    var q = input.value.toLowerCase().trim();
                    root.querySelectorAll('tr[data-search]').forEach(function(tr) {
                        tr.style.display = tr.getAttribute('data-search').toLowerCase().indexOf(q) > -1 ? '' : 'none';
                    });
                });
            }
            root.querySelectorAll('[data-action="report-nudge"]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    sendNudge(btn, btn.getAttribute('data-userid'), btn.getAttribute('data-courseid'), '', null);
                });
            });
        }
    };
});
