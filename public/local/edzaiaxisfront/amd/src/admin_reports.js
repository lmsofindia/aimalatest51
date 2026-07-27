// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Admin token usage reports.
 *
 * - Loads report data via AJAX on init and on month/search change
 * - Renders sortable table with usage % progress bars
 * - Supports drilling into a user's daily breakdown (modal)
 * - CSV export via direct link
 *
 * @module     local_edzaiaxisfront/admin_reports
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Modal from 'core/modal';
import ModalEvents from 'core/modal_events';

let cfg = {};
let currentSort = {col: 'tokens_month', dir: 'desc'};
let allUsers    = [];

export const init = (initCfg) => {
    cfg = initCfg;

    attachFilterEvents();
    loadReport(cfg.month_year, cfg.search, 0);
};

// ── Data loading ──────────────────────────────────────────────────────────────

const loadReport = (month_year, search, page) => {
    const table = document.getElementById('edzai-report-table-body');
    const stats = document.getElementById('edzai-report-stats');
    if (table) {
        table.innerHTML =
            '<tr><td colspan="7" class="text-center">' +
            '<i class="fa fa-spinner fa-spin"></i> Loading\u2026</td></tr>';
    }

    Ajax.call([{
        methodname: 'local_edzaiaxisfront_get_token_usage_report',
        args: {month_year, search, page, perpage: 100},
    }])[0].then(data => {
        allUsers = data.users;
        renderStats(stats, data);
        renderTable(table, data.users);
        updateExportLink(month_year, search);
    }).catch(Notification.exception);
};

const renderStats = (el, data) => {
    if (!el) {
        return;
    }
    const pct = data.site_limit > 0
        ? Math.round((data.site_tokens_month / data.site_limit) * 100)
        : 0;
    const barClass = pct >= 90 ? 'danger' : pct >= 70 ? 'warning' : 'success';

    el.innerHTML = `
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Total Tokens Used</h5>
                    <p class="display-4">${formatNum(data.site_tokens_month)}</p>
                    <small class="text-muted">${data.month_year}</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Site Limit</h5>
                    <p class="display-4">${data.site_limit > 0 ? formatNum(data.site_limit) : '\u221e'}</p>
                    <small class="text-muted">monthly</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Site Usage</h5>
                    <p class="display-4 text-${barClass}">${pct}%</p>
                    <div class="progress" style="height:8px">
                        <div class="progress-bar bg-${barClass}"
                             style="width:${Math.min(pct, 100)}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
};

const renderTable = (tbody, users) => {
    if (!tbody) {
        return;
    }

    // Sort
    const sorted = [...users].sort((a, b) => {
        const av = a[currentSort.col] ?? 0;
        const bv = b[currentSort.col] ?? 0;
        return currentSort.dir === 'asc' ? (av > bv ? 1 : -1) : (av < bv ? 1 : -1);
    });

    if (!sorted.length) {
        tbody.innerHTML =
            '<tr><td colspan="7" class="text-center text-muted">' +
            'No usage data for this period.</td></tr>';
        return;
    }

    tbody.innerHTML = sorted.map(u => {
        const pct      = u.usage_pct;
        const barClass = pct >= 90 ? 'danger' : pct >= 70 ? 'warning' : 'success';
        const limitStr = u.effective_limit > 0 ? formatNum(u.effective_limit) : '\u221e';
        const override = u.has_override
            ? '<span class="badge badge-warning ml-1">override</span>'
            : '';
        const warnCls  = pct >= 90 ? 'text-danger font-weight-bold' : '';
        const lastAct  = u.last_activity
            ? new Date(u.last_activity * 1000).toLocaleDateString()
            : '\u2014';
        const pctBar = Math.min(pct, 100);
        const overrideUrl = (cfg.overrides_url || '#') + '?userid=' + u.userid;

        return `
        <tr>
            <td>${escHtml(u.fullname)} ${override}</td>
            <td><small class="text-muted">${escHtml(u.username)}</small></td>
            <td class="text-right font-weight-bold">${formatNum(u.tokens_month)}</td>
            <td>${limitStr}</td>
            <td style="min-width:120px">
                <div class="d-flex align-items-center">
                    <div class="progress flex-fill mr-2" style="height:8px">
                        <div class="progress-bar bg-${barClass}"
                             style="width:${pctBar}%"></div>
                    </div>
                    <small class="${warnCls}">${pct}%</small>
                </div>
            </td>
            <td><small>${lastAct}</small></td>
            <td>
                <button class="btn btn-sm btn-outline-info edzai-btn-daily mr-1"
                    data-userid="${u.userid}"
                    data-name="${escHtml(u.fullname)}">
                    <i class="fa fa-bar-chart"></i> Daily
                </button>
                <a href="${overrideUrl}"
                   class="btn btn-sm btn-outline-secondary ml-1">
                    <i class="fa fa-sliders"></i> Override
                </a>
            </td>
        </tr>`;
    }).join('');
};

// ── Daily breakdown modal ──────────────────────────────────────────────────────

const showDailyModal = async(userid, name, month_year) => {
    const data = await Ajax.call([{
        methodname: 'local_edzaiaxisfront_get_user_daily_usage',
        args: {userid, month_year},
    }])[0];

    const rows = data.days.map(d => `
    <tr>
        <td>${d.day_date}</td>
        <td class="text-right">${formatNum(d.tokens_day)}</td>
    </tr>`).join('');

    const noData = '<tr><td colspan="2" class="text-muted">No data</td></tr>';

    // Moodle 4.3+ / 5.x: use Modal.create() — ModalFactory is deprecated
    const modal = await Modal.create({
        title: `Daily usage: ${name} \u2014 ${month_year}`,
        body: `
        <table class="table table-sm table-striped">
            <thead><tr><th>Date</th><th class="text-right">Tokens</th></tr></thead>
            <tbody>${rows || noData}</tbody>
        </table>`,
    });
    modal.getRoot().on(ModalEvents.hidden, () => {
        modal.destroy();
    });
    modal.show();
};

// ── Events ────────────────────────────────────────────────────────────────────

const attachFilterEvents = () => {
    const monthSel = document.getElementById('edzai-report-month');
    const searchEl = document.getElementById('edzai-report-search');
    const applyBtn = document.getElementById('edzai-report-apply');

    if (applyBtn) {
        applyBtn.addEventListener('click', () => {
            const m = monthSel?.value || cfg.month_year;
            const s = searchEl?.value || '';
            cfg.month_year = m;
            loadReport(m, s, 0);
        });
    }

    // Sort headers
    document.addEventListener('click', e => {
        const th = e.target.closest('.edzai-sortable');
        if (!th) {
            return;
        }
        const col = th.dataset.col;
        if (currentSort.col === col) {
            currentSort.dir = currentSort.dir === 'asc' ? 'desc' : 'asc';
        } else {
            currentSort = {col, dir: 'desc'};
        }
        renderTable(document.getElementById('edzai-report-table-body'), allUsers);
    });

    // Daily drill-down
    document.addEventListener('click', e => {
        const btn = e.target.closest('.edzai-btn-daily');
        if (!btn) {
            return;
        }
        showDailyModal(parseInt(btn.dataset.userid), btn.dataset.name, cfg.month_year);
    });
};

const updateExportLink = (month_year, search) => {
    const link = document.getElementById('edzai-export-link');
    if (!link) {
        return;
    }
    const url = new URL(link.href);
    url.searchParams.set('month', month_year);
    url.searchParams.set('search', search || '');
    link.href = url.toString();
};

// ── Utilities ──────────────────────────────────────────────────────────────────

const formatNum = (n) => Number(n).toLocaleString();
const escHtml   = (s) =>
    String(s || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
