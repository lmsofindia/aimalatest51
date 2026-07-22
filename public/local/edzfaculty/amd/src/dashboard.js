// This file is part of the local_edzfaculty plugin for Moodle.
//
// Faculty dashboard interactivity: tabs, course focus, engagement filter,
// section chart, at-risk student modal, and manual analytics refresh.

/**
 * @module local_edzfaculty/dashboard
 * @copyright 2026 EDZLEARN
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/chartjs', 'core/ajax', 'core/notification'], function(Chart, Ajax, Notification) {
    'use strict';

    var data = {};

    /**
     * Format an integer with thousands separators.
     * @param {number} n
     * @return {string}
     */
    function num(n) {
        return String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /**
     * Trend arrow text.
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
     * Trend css class.
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
     * Tab switching.
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
     * Apply a Course Focus scope (0 = all).
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
        var sel = root.querySelector('#edzf-course');
        if (sel && String(sel.value) !== String(cid)) {
            sel.value = String(cid);
        }
    }

    /**
     * Course Focus selector.
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
     * Course cards drive Course Focus + engagement.
     * @param {HTMLElement} root
     */
    function wireCourseCards(root) {
        root.querySelectorAll('[data-action="course-card"]').forEach(function(card) {
            card.addEventListener('click', function(e) {
                e.preventDefault();
                var cid = card.getAttribute('data-courseid');
                applyFocus(root, cid);
                applyEngagement(root, cid);
                var focus = root.querySelector('.edzf-focus');
                if (focus) {
                    focus.scrollIntoView({behavior: 'smooth', block: 'center'});
                }
            });
        });
    }

    /**
     * Build the AI insight string for a scope.
     * @param {number|string} cid
     * @param {object} e engagement metrics
     * @return {string}
     */
    function insight(cid, e) {
        if (!cid) {
            var labels = data.chart.labels || [];
            var values = data.chart.values || [];
            if (labels.length) {
                var mini = 0;
                values.forEach(function(v, i) {
                    if (v < values[mini]) {
                        mini = i;
                    }
                });
                return labels[mini] + ' is currently your lowest section at ' + values[mini] +
                    '%. Consider a revision session.';
            }
        }
        return 'Active students ' + Math.round(e.active.value) + '%, average score ' +
            Math.round(e.score.value) + '% ' + trendText(e.score.trend) + '.';
    }

    /**
     * Apply an engagement scope.
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
        root.querySelectorAll('.edzf-seg').forEach(function(s) {
            s.classList.toggle('active', String(s.getAttribute('data-eng')) === String(cid));
        });
        var ai = root.querySelector('[data-region="eng-ai"]');
        if (ai && data.showai) {
            ai.innerHTML = '<b>' + data.strings.aiinsight + ':</b> ' + insight(cid, e);
        }
    }

    /**
     * Engagement filter pills.
     * @param {HTMLElement} root
     */
    function wireEngagement(root) {
        root.querySelectorAll('.edzf-seg').forEach(function(seg) {
            seg.addEventListener('click', function() {
                applyEngagement(root, seg.getAttribute('data-eng'));
            });
        });
    }

    /**
     * Section performance bar chart via core/chartjs.
     * @param {HTMLElement} root
     */
    function renderChart(root) {
        var canvas = root.querySelector('[data-region="edzf-chart"]');
        if (!canvas || !data.chart || !data.chart.labels.length) {
            return;
        }
        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: data.chart.labels,
                datasets: [{
                    label: data.strings.score,
                    data: data.chart.values,
                    backgroundColor: '#0d7a47',
                    borderRadius: 4,
                    maxBarThickness: 46
                }]
            },
            options: {
                responsive: true,
                plugins: {legend: {display: false}},
                scales: {y: {beginAtZero: true, max: 100, ticks: {callback: function(v) { return v + '%'; }}}}
            }
        });
    }

    /**
     * At-risk student modal (lightweight, dependency-free).
     * @param {HTMLElement} root
     */
    function wireStudents(root) {
        root.querySelectorAll('[data-action="student"]').forEach(function(row) {
            row.addEventListener('click', function() {
                openStudent(row);
            });
        });
    }

    /**
     * Escape HTML for safe insertion.
     * @param {string} s
     * @return {string}
     */
    function esc(s) {
        var div = document.createElement('div');
        div.textContent = s == null ? '' : String(s);
        return div.innerHTML;
    }

    /**
     * Build and show the student overview modal (with nudge box).
     * @param {HTMLElement} row
     */
    function openStudent(row) {
        var d = row.dataset;
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

        var close = function() {
            document.removeEventListener('keydown', onkey);
            overlay.remove();
        };
        var onkey = function(e) {
            if (e.key === 'Escape') {
                close();
            }
        };
        document.addEventListener('keydown', onkey);
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                close();
            }
        });
        overlay.querySelector('.edzf-mclose').addEventListener('click', close);
        overlay.querySelector('.edzf-mclose').focus();

        overlay.querySelector('[data-action="nudge-send"]').addEventListener('click', function(btn) {
            var target = btn.currentTarget;
            var body = overlay.querySelector('.edzf-nudge').value;
            target.disabled = true;
            Ajax.call([{
                methodname: 'local_edzfaculty_send_nudge',
                args: {studentid: parseInt(d.userid, 10), courseid: parseInt(d.courseid, 10), message: body}
            }])[0].then(function(res) {
                Notification.addNotification({message: res.message, type: res.sent ? 'success' : 'warning'});
                if (res.sent) {
                    close();
                }
                return res;
            }).catch(Notification.exception).always(function() {
                target.disabled = false;
            });
        });
    }

    /**
     * Manual analytics refresh.
     * @param {HTMLElement} root
     */
    function wireRefresh(root) {
        var btn = root.querySelector('[data-action="refresh"]');
        if (!btn) {
            return;
        }
        btn.addEventListener('click', function() {
            btn.disabled = true;
            Ajax.call([{methodname: 'local_edzfaculty_refresh_cache', args: {}}])[0]
                .then(function(res) {
                    Notification.addNotification({
                        message: res.message,
                        type: res.queued ? 'info' : 'warning'
                    });
                    return res;
                })
                .catch(Notification.exception)
                .always(function() {
                    btn.disabled = false;
                });
        });
    }

    return {
        /**
         * Entry point.
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
            wireCourseCards(root);
            wireEngagement(root);
            wireRefresh(root);
            wireStudents(root);
            renderChart(root);
            applyEngagement(root, 0);
        },

        /**
         * Overview table: make rows clickable.
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
        }
    };
});
