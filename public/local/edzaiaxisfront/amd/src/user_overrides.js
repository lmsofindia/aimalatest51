// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Per-user token limit override management.
 *
 * @module     local_edzaiaxisfront/user_overrides
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from "core/ajax";
import Notification from "core/notification";
import SaveCancelModal from "core/modal_save_cancel";
import ModalEvents from "core/modal_events";

let cfg = {};

// ── Init ───────────────────────────────────────────────────────────────────

export const init = (initCfg) => {
    cfg = initCfg;
    loadOverrides();
    attachEvents();
};

// ── Data ───────────────────────────────────────────────────────────────────

const loadOverrides = () => {
    const tbody = document.getElementById("edzai-overrides-tbody");
    if (tbody) {
        tbody.innerHTML =
            "<tr><td colspan=\"7\" class=\"text-center\"><i class=\"fa fa-spinner fa-spin\"></i></td></tr>";
    }
    Ajax.call([{methodname: "local_edzaiaxisfront_list_user_overrides", args: {}}])[0]
        .then((items) => renderOverridesTable(tbody, items))
        .catch(Notification.exception);
};

const renderOverridesTable = (tbody, items) => {
    if (!tbody) {
        return;
    }
    if (!items.length) {
        tbody.innerHTML =
            "<tr><td colspan=\"7\" class=\"text-center text-muted\">" +
            "No overrides set. All users are using site defaults.</td></tr>";
        return;
    }
    const def = cfg.site_defaults;
    const fmt = (v, dv) =>
        v === -1 ? `<span class="text-muted">default (${dv})</span>` : `<strong>${v}</strong>`;

    tbody.innerHTML = items.map((item) => `
        <tr data-userid="${item.userid}">
            <td>
                <strong>${escHtml(item.fullname)}</strong><br>
                <small class="text-muted">${escHtml(item.email)}</small>
            </td>
            <td>${fmt(item.chat_session_msg_limit, def.chat_session_msg_limit)}</td>
            <td>${fmt(item.chat_daily_msg_limit, def.chat_daily_msg_limit)}</td>
            <td>${fmt(item.chat_monthly_msg_limit, def.chat_monthly_msg_limit)}</td>
            <td>${fmt(item.token_monthly_limit, def.token_monthly_limit)}</td>
            <td>
                ${item.note
                    ? `<span title="${escHtml(item.note)}"><i class="fa fa-sticky-note text-info"></i></span>`
                    : ""}
                <small class="text-muted">${item.set_by ? `by ${escHtml(item.set_by)}` : ""}</small>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-primary edzai-btn-edit-override mr-1"
                    data-userid="${item.userid}"
                    data-fullname="${escHtml(item.fullname)}"
                    data-session="${item.chat_session_msg_limit}"
                    data-daily="${item.chat_daily_msg_limit}"
                    data-monthly="${item.chat_monthly_msg_limit}"
                    data-tokens="${item.token_monthly_limit}"
                    data-note="${escHtml(item.note)}">
                    <i class="fa fa-pencil"></i> Edit
                </button>
                <button class="btn btn-sm btn-outline-danger edzai-btn-delete-override"
                    data-userid="${item.userid}"
                    data-fullname="${escHtml(item.fullname)}">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>`).join("");
};

// ── User search autocomplete ───────────────────────────────────────────────

let searchTimer = null;

const initUserSearch = () => {
    const input = document.getElementById("edzai-user-search-input");
    const results = document.getElementById("edzai-user-search-results");
    const hiddenId = document.getElementById("edzai-user-search-id");
    const nameSpan = document.getElementById("edzai-user-search-name");

    if (!input || !results) {
        return;
    }

    input.addEventListener("input", () => {
        clearTimeout(searchTimer);
        const q = input.value.trim();
        hiddenId.value = "";
        nameSpan.textContent = "";
        if (q.length < 2) {
            closeDropdown(results);
            return;
        }
        searchTimer = window.setTimeout(
            () => doUserSearch(q, results, hiddenId, nameSpan, input),
            300
        );
    });

    document.addEventListener("click", (e) => {
        if (!input.contains(e.target) && !results.contains(e.target)) {
            closeDropdown(results);
        }
    });

    input.addEventListener("keydown", (e) => {
        const items = results.querySelectorAll(".edzai-search-item");
        const active = results.querySelector(".edzai-search-item.active");
        if (e.key === "ArrowDown") {
            e.preventDefault();
            if (!active && items.length) {
                items[0].classList.add("active");
            } else if (active && active.nextElementSibling) {
                active.classList.remove("active");
                active.nextElementSibling.classList.add("active");
            }
        } else if (e.key === "ArrowUp") {
            e.preventDefault();
            if (active && active.previousElementSibling) {
                active.classList.remove("active");
                active.previousElementSibling.classList.add("active");
            }
        } else if (e.key === "Enter") {
            e.preventDefault();
            if (active) {
                active.click();
            }
        } else if (e.key === "Escape") {
            closeDropdown(results);
        }
    });
};

const doUserSearch = (query, results, hiddenId, nameSpan, input) => {
    results.innerHTML =
        "<div class=\"dropdown-item text-muted\"><i class=\"fa fa-spinner fa-spin\"></i> Searching\u2026</div>";
    results.classList.add("show");

    Ajax.call([{methodname: "local_edzaiaxisfront_search_users", args: {query}}])[0]
        .then((users) => {
            results.innerHTML = "";
            if (!users.length) {
                results.innerHTML = "<div class=\"dropdown-item text-muted\">No users found.</div>";
                return;
            }
            users.forEach((user) => {
                const item = document.createElement("button");
                item.type = "button";
                item.className = "dropdown-item edzai-search-item";
                item.innerHTML = `
                    <strong>${escHtml(user.fullname)}</strong>
                    <small class="text-muted ml-1">${escHtml(user.username)} \u2014 ${escHtml(user.email)}</small>`;
                item.addEventListener("click", () => {
                    hiddenId.value = user.id;
                    nameSpan.textContent = user.fullname;
                    input.value = user.fullname;
                    closeDropdown(results);
                });
                results.appendChild(item);
            });
        })
        .catch(() => {
            results.innerHTML =
                "<div class=\"dropdown-item text-danger\">Search failed. Please try again.</div>";
        });
};

const closeDropdown = (results) => {
    results.innerHTML = "";
    results.classList.remove("show");
};

// ── Override modal ─────────────────────────────────────────────────────────

const showOverrideModal = async(userid, fullname, existing = {}) => {
    const def = cfg.site_defaults;
    const val = (v) => (v !== undefined && v !== -1 ? v : "");

    const body = `
        <form id="edzai-override-form">
            <p>
                Setting limits for: <strong>${escHtml(fullname)}</strong><br>
                <small class="text-muted">Leave blank to use site default.</small>
            </p>
            ${buildInput("ov-session", "Messages per Session", val(existing.session), def.chat_session_msg_limit)}
            ${buildInput("ov-daily", "Messages per Day", val(existing.daily), def.chat_daily_msg_limit)}
            ${buildInput("ov-monthly", "Messages per Month", val(existing.monthly), def.chat_monthly_msg_limit)}
            ${buildInput("ov-tokens", "Tokens per Month", val(existing.tokens), def.token_monthly_limit)}
            <div class="form-group row">
                <label class="col-sm-5 col-form-label">Admin Note</label>
                <div class="col-sm-7">
                    <input type="text" class="form-control" id="ov-note"
                           maxlength="255" value="${escHtml(existing.note || "")}">
                </div>
            </div>
        </form>`;

    // Moodle 4.3+ / 5.x: use SaveCancelModal.create() directly
    const modal = await SaveCancelModal.create({
        title: `Set Limits \u2014 ${escHtml(fullname)}`,
        body,
    });

    modal.getRoot().on(ModalEvents.save, async(e) => {
        e.preventDefault();
        const toInt = (id) => {
            const v = document.getElementById(id)?.value.trim();
            return v === "" ? -1 : parseInt(v, 10);
        };
        try {
            await Ajax.call([{
                methodname: "local_edzaiaxisfront_save_user_override",
                args: {
                    userid,
                    chat_session_msg_limit: toInt("ov-session"),
                    chat_daily_msg_limit: toInt("ov-daily"),
                    chat_monthly_msg_limit: toInt("ov-monthly"),
                    token_monthly_limit: toInt("ov-tokens"),
                    note: document.getElementById("ov-note")?.value || "",
                },
            }])[0];
            modal.destroy();
            loadOverrides();
        } catch (err) {
            Notification.exception(err);
        }
    });

    modal.show();
};

const buildInput = (id, label, value, def) => `
    <div class="form-group row">
        <label class="col-sm-5 col-form-label">${label}</label>
        <div class="col-sm-7">
            <input type="number" class="form-control" id="${id}"
                   value="${value}" placeholder="Default: ${def}">
        </div>
    </div>`;

// ── Events ─────────────────────────────────────────────────────────────────

const attachEvents = () => {
    initUserSearch();

    document.getElementById("edzai-btn-add-override")?.addEventListener("click", async() => {
        const userId = document.getElementById("edzai-user-search-id")?.value;
        const userName = document.getElementById("edzai-user-search-name")?.textContent?.trim();
        if (!userId) {
            // eslint-disable-next-line no-alert
            window.alert("Please select a user first by typing their name in the search box.");
            return;
        }
        await showOverrideModal(parseInt(userId, 10), userName || "User");
    });

    document.addEventListener("click", async(e) => {
        const editBtn = e.target.closest(".edzai-btn-edit-override");
        if (editBtn) {
            await showOverrideModal(
                parseInt(editBtn.dataset.userid, 10),
                editBtn.dataset.fullname,
                {
                    session: parseInt(editBtn.dataset.session, 10),
                    daily: parseInt(editBtn.dataset.daily, 10),
                    monthly: parseInt(editBtn.dataset.monthly, 10),
                    tokens: parseInt(editBtn.dataset.tokens, 10),
                    note: editBtn.dataset.note,
                }
            );
            return;
        }

        const delBtn = e.target.closest(".edzai-btn-delete-override");
        if (delBtn) {
            // eslint-disable-next-line no-alert
            if (!window.confirm(`Delete override for ${delBtn.dataset.fullname}?`)) {
                return;
            }
            try {
                await Ajax.call([{
                    methodname: "local_edzaiaxisfront_delete_user_override",
                    args: {userid: parseInt(delBtn.dataset.userid, 10)},
                }])[0];
                loadOverrides();
            } catch (err) {
                Notification.exception(err);
            }
        }
    });

    const syncBtn = document.getElementById("edzai-btn-sync-settings");
    if (syncBtn) {
        syncBtn.addEventListener("click", async() => {
            syncBtn.disabled = true;
            syncBtn.innerHTML = "<i class=\"fa fa-spinner fa-spin\"></i> Syncing\u2026";
            try {
                await Ajax.call([{
                    methodname: "local_edzaiaxisfront_sync_tenant_settings",
                    args: {},
                }])[0];
                syncBtn.innerHTML = "<i class=\"fa fa-check\"></i> Synced!";
                syncBtn.classList.replace("btn-outline-warning", "btn-success");
                window.setTimeout(() => {
                    syncBtn.disabled = false;
                    syncBtn.innerHTML = "<i class=\"fa fa-refresh\"></i> Sync rate limits to axis-ai";
                    syncBtn.classList.replace("btn-success", "btn-outline-warning");
                }, 3000);
            } catch (err) {
                syncBtn.disabled = false;
                syncBtn.innerHTML = "<i class=\"fa fa-refresh\"></i> Sync rate limits to axis-ai";
                Notification.exception(err);
            }
        });
    }
};

// ── Utilities ──────────────────────────────────────────────────────────────

const escHtml = (s) =>
    String(s || "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
