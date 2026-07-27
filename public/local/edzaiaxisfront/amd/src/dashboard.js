// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Dashboard AMD module — loads all course CMs and renders the status table.
 *
 * @module     local_edzaiaxisfront/dashboard
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Repository from "local_edzaiaxisfront/repository";
import * as JobMonitor from "local_edzaiaxisfront/job_monitor";
import Notification from "core/notification";
import {get_string as getString} from "core/str";
import Modal from "core/modal";
import ModalEvents from "core/modal_events";

let config = {};

const STATUS_ICONS = {
    not_ingested: "circle-o",
    processing: "spinner fa-spin",
    ready: "check-circle text-success",
    failed: "times-circle text-danger",
};

/**
 * Initialise the dashboard.
 * Called from dashboard.php via $PAGE->requires->js_call_amd.
 *
 * @param {Object} cfg
 */
export const init = (cfg) => {
    config = cfg;
    loadCms();
    document.addEventListener("click", handleTableClick);
};

// ── Data loading ───────────────────────────────────────────────────────────

const loadCms = async() => {
    const container = document.getElementById("edzai-dashboard-cms");
    if (!container) {
        return;
    }
    try {
        const cms = await Repository.getCourseCms(config.courseid);
        await renderTable(container, cms);
    } catch (e) {
        Notification.exception(e);
    }
};

// ── Table rendering ────────────────────────────────────────────────────────

const renderTable = async(container, cms) => {
    if (!cms.length) {
        // Use async getString — never call M.util.get_string inside AMD modules
        const noStr = await getString("no_supported_cms", "local_edzaiaxisfront");
        container.innerHTML = `<p class="text-muted">${noStr}</p>`;
        return;
    }

    const rows = cms.map((cm) => {
        const generated = safeParseJson(cm.generated_features, []);
        const icon = STATUS_ICONS[cm.status] || STATUS_ICONS.not_ingested;
        const featBadges = generated
            .map((f) => `<span class="badge badge-secondary mr-1">${escHtml(f)}</span>`)
            .join("");

        return `
        <tr data-cmid="${cm.cmid}" data-status="${cm.status}">
            <td>
                <a href="${config.wizardurl}&amp;cmid=${cm.cmid}">
                    <i class="fa fa-${icon}"></i> ${escHtml(cm.name)}
                </a>
            </td>
            <td><span class="badge badge-light">${escHtml(cm.modname)}</span></td>
            <td><span class="badge badge-info">${escHtml(cm.content_type)}</span></td>
            <td>
                <i class="fa fa-${icon}"></i>
                <span class="edzai-status-label">${escHtml(cm.status)}</span>
            </td>
            <td>${featBadges || "\u2014"}</td>
            <td>
                <a href="${config.wizardurl}&amp;cmid=${cm.cmid}"
                   class="btn btn-sm btn-primary edzai-btn-wizard">
                    ${cm.status === "not_ingested" ? "Set up AI" : "Edit / Regenerate"}
                </a>
                ${cm.status === "ready"
                    ? `<button class="btn btn-sm btn-outline-info ml-1 edzai-btn-view"
                               data-cmid="${cm.cmid}">\u270F Edit Content</button>`
                    : ""}
            </td>
        </tr>`;
    }).join("");

    container.innerHTML = `
    <table class="table table-striped table-hover generaltable" id="edzai-cm-table">
        <thead>
            <tr>
                <th>Activity</th>
                <th>Type</th>
                <th>Content Type</th>
                <th>Status</th>
                <th>Generated</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>${rows}</tbody>
    </table>`;

    // Live-refresh any rows still processing so the admin never has to reload.
    startLiveRefresh();
};

// ── Live status refresh ─────────────────────────────────────────────────────
// Polls get_job_status for every row that's still queued/processing (via the
// shared job_monitor), updates the status cell in place, and re-renders the
// table once everything settles so badges + "Edit Content" appear correctly.

const STATUS_LABELS = {
    queued: "queued",
    processing: "processing",
    ready: "ready",
    failed: "failed",
    not_ingested: "not ingested",
};

const startLiveRefresh = () => {
    const pending = [...document.querySelectorAll("#edzai-cm-table tbody tr")]
        .filter((tr) => ["processing", "queued"].includes(tr.dataset.status))
        .map((tr) => ({cmid: parseInt(tr.dataset.cmid, 10), status: tr.dataset.status}));

    JobMonitor.stop();
    if (!pending.length) {
        return;
    }

    JobMonitor.start(pending, {
        onUpdate: (cmid, status) => {
            const tr = document.querySelector(`#edzai-cm-table tr[data-cmid="${cmid}"]`);
            if (!tr) {
                return;
            }
            tr.dataset.status = status;
            const statusCell = tr.querySelectorAll("td")[3];
            if (statusCell) {
                const icon = STATUS_ICONS[status] || STATUS_ICONS.not_ingested;
                statusCell.innerHTML =
                    `<i class="fa fa-${icon}"></i> ` +
                    `<span class="edzai-status-label">${STATUS_LABELS[status] || status}</span>`;
            }
        },
        // When nothing is left processing, re-render so the Generated badges and the
        // "Edit Content" button reflect the final state.
        onAllDone: () => {
            loadCms();
        },
    });
};

// ── Table click delegation ─────────────────────────────────────────────────

const handleTableClick = async(e) => {
    const viewBtn = e.target.closest(".edzai-btn-view");
    if (viewBtn) {
        await showOutputsModal(parseInt(viewBtn.dataset.cmid, 10));
    }
};

// ── Edit modal ─────────────────────────────────────────────────────────────

let _editModal = null;

// Modern line icons (inherit currentColor).
const ICON_TRASH = '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>';
const ICON_PLUS = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>';

const showOutputsModal = async(cmid) => {
    let outputs;
    try {
        outputs = await Repository.getTeacherCmOutputs(cmid);
    } catch (e) {
        Notification.exception(e);
        return;
    }

    const tabs = buildEditTabs(outputs);
    if (!tabs.length) {
        Notification.addNotification({
            message: "No editable AI outputs available for this activity yet.",
            type: "info",
        });
        return;
    }

    if (_editModal) {
        _editModal.destroy();
        _editModal = null;
    }

    // Moodle 4.3+ / 5.x: use Modal.create() directly — ModalFactory is deprecated
    const modal = await Modal.create({
        title: "Edit AI Content \u2014 " + escHtml(outputs.cm_name || ("CM " + cmid)),
        body: buildModalBody(tabs),
        large: true,
        removeOnClose: true,
    });

    _editModal = modal;
    modal.show();

    // getBodyPromise() resolves once Moodle finishes async-rendering the body
    // into the live DOM — the only safe place to querySelector modal elements.
    modal.getBodyPromise().then((bodyEl) => {
        const root = bodyEl[0]; // jQuery → raw DOM element
        bindModalTabs(root);
        bindModalSave(root, cmid);
    });

    modal.getRoot().on(ModalEvents.hidden, () => {
        _editModal = null;
    });
};

// ── Tab definitions ────────────────────────────────────────────────────────

const buildEditTabs = (outputs) => {
    const tabs = [];
    if (outputs.summary) {
        tabs.push({id: "summary", label: "Summary", content: buildSummaryTab(outputs.summary)});
    }
    if (outputs.glossary) {
        tabs.push({id: "glossary", label: "Glossary", content: buildGlossaryTab(safeParseJson(outputs.glossary, []))});
    }
    if (outputs.flashcards) {
        tabs.push({id: "flashcards", label: "Flashcards", content: buildFlashcardsTab(safeParseJson(outputs.flashcards, []))});
    }
    if (outputs.quiz) {
        tabs.push({id: "quiz", label: "Quiz", content: buildQuizTab(safeParseJson(outputs.quiz, []))});
    }
    if (outputs.faq) {
        tabs.push({id: "faq", label: "FAQ", content: buildFaqTab(safeParseJson(outputs.faq, []))});
    }
    if (outputs.infographic) {
        tabs.push({id: "infographic", label: "Infographic", content: buildInfographicTab(outputs.infographic)});
    }
    return tabs;
};

// ── Tab content builders ───────────────────────────────────────────────────

const buildSummaryTab = (summaryHtml) => `
  <div class="edzai-edit-section">
    <p class="text-muted small mb-2">Edit the AI-generated summary. HTML is supported.</p>
    <textarea class="form-control edzai-summary-textarea" rows="14"
              id="edzai-edit-summary">${escHtml(summaryHtml)}</textarea>
  </div>`;

const buildGlossaryTab = (terms) => {
    const rows = terms.map((t, i) => `
      <div class="edzai-glossary-row mb-2" data-idx="${i}">
        <div class="d-flex align-items-start">
          <input type="text" class="form-control edzai-gloss-term mr-2"
                 placeholder="Term" value="${escHtml(t.term || t.word || "")}"
                 style="flex:0 0 35%">
          <textarea class="form-control edzai-gloss-def mr-2" rows="2"
                    placeholder="Definition" style="flex:1">${escHtml(t.definition || t.desc || "")}</textarea>
          <button class="btn btn-sm btn-outline-danger edzai-gloss-del flex-shrink-0"
                  title="Remove">${ICON_TRASH}</button>
        </div>
      </div>`).join("");

    return `
      <div class="edzai-edit-section">
        <p class="text-muted small mb-2">Edit glossary terms and definitions.</p>
        <div id="edzai-glossary-list">${rows}</div>
        <button class="btn btn-sm btn-outline-secondary mt-2" id="edzai-gloss-add">${ICON_PLUS} Add term</button>
      </div>`;
};

const buildFlashcardsTab = (cards) => {
    const rows = cards.map((c, i) => `
      <div class="edzai-flashcard-row border rounded p-2 mb-2" data-idx="${i}">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <strong class="small text-muted">Card ${i + 1}</strong>
          <button class="btn btn-sm btn-outline-danger edzai-card-del">${ICON_TRASH}</button>
        </div>
        <div class="form-group mb-1">
          <label class="small font-weight-bold">Front (question)</label>
          <textarea class="form-control edzai-card-front" rows="2">${escHtml(c.front || c.question || "")}</textarea>
        </div>
        <div class="form-group mb-0">
          <label class="small font-weight-bold">Back (answer)</label>
          <textarea class="form-control edzai-card-back" rows="2">${escHtml(c.back || c.answer || "")}</textarea>
        </div>
      </div>`).join("");

    return `
      <div class="edzai-edit-section">
        <p class="text-muted small mb-2">Edit flashcard front/back pairs.</p>
        <div id="edzai-flashcards-list">${rows}</div>
        <button class="btn btn-sm btn-outline-secondary mt-2" id="edzai-card-add">${ICON_PLUS} Add card</button>
      </div>`;
};

const buildFaqTab = (faqs) => {
    const rows = faqs.map((f, i) => `
      <div class="edzai-faq-row border rounded p-2 mb-2" data-idx="${i}">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <strong class="small text-muted">Q${i + 1}</strong>
          <button class="btn btn-sm btn-outline-danger edzai-faq-del" title="Remove">${ICON_TRASH}</button>
        </div>
        <div class="form-group mb-1">
          <label class="small font-weight-bold">Question</label>
          <textarea class="form-control edzai-faq-q" rows="2">${escHtml(f.question || f.q || "")}</textarea>
        </div>
        <div class="form-group mb-0">
          <label class="small font-weight-bold">Answer</label>
          <textarea class="form-control edzai-faq-a" rows="3">${escHtml(f.answer || f.a || "")}</textarea>
        </div>
      </div>`).join("");

    return `
      <div class="edzai-edit-section">
        <p class="text-muted small mb-2">Edit frequently asked questions and their answers.</p>
        <div id="edzai-faq-list">${rows}</div>
        <button class="btn btn-sm btn-outline-secondary mt-2" id="edzai-faq-add">${ICON_PLUS} Add FAQ</button>
      </div>`;
};

const buildInfographicTab = (html) => `
  <div class="edzai-edit-section">
    <p class="text-muted small mb-2">Infographic preview (read-only). To change it, use Edit / Regenerate on the activity.</p>
    <div class="edzai-infographic-preview border rounded p-3" style="max-height:520px;overflow:auto;background:#fff">${html || "<em class='text-muted'>No infographic generated.</em>"}</div>
  </div>`;

const buildQuizTab = (questions) => {
    const rows = questions.map((q, i) => {
        const options = q.options || q.choices || [];
        const opts = options.map((opt, oi) => `
          <div class="d-flex align-items-center mb-1">
            <input type="radio" name="edzai-q${i}-correct" value="${oi}" class="mr-2"
                   ${(q.correct_index === oi || q.answer === oi) ? "checked" : ""}>
            <input type="text" class="form-control form-control-sm edzai-opt-text"
                   value="${escHtml(typeof opt === "string" ? opt : (opt.text || opt.label || ""))}">
          </div>`).join("");

        return `
          <div class="edzai-quiz-row border rounded p-2 mb-2" data-idx="${i}">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <strong class="small text-muted">Q${i + 1}</strong>
              <button class="btn btn-sm btn-outline-danger edzai-q-del">${ICON_TRASH}</button>
            </div>
            <div class="form-group mb-2">
              <label class="small font-weight-bold">Question</label>
              <textarea class="form-control edzai-q-text" rows="2">${escHtml(q.question || q.text || "")}</textarea>
            </div>
            <div class="form-group mb-1">
              <label class="small font-weight-bold">Options
                <span class="text-muted font-weight-normal">(select correct answer)</span>
              </label>
              <div class="edzai-opts-list">${opts}</div>
            </div>
            ${q.explanation ? `
            <div class="form-group mb-0">
              <label class="small font-weight-bold">Explanation</label>
              <textarea class="form-control edzai-q-expl" rows="2">${escHtml(q.explanation)}</textarea>
            </div>` : ""}
          </div>`;
    }).join("");

    return `
      <div class="edzai-edit-section">
        <p class="text-muted small mb-2">Edit quiz questions. Select the radio button for the correct answer.</p>
        <div id="edzai-quiz-list">${rows}</div>
        <button class="btn btn-sm btn-outline-secondary mt-2" id="edzai-q-add">${ICON_PLUS} Add question</button>
      </div>`;
};

// ── Modal body assembly ────────────────────────────────────────────────────

const buildModalBody = (tabs) => {
    // Tab nav — JS-driven clicks, no Bootstrap data attributes, works on BS4 and BS5
    const tabHeaders = tabs.map((t, i) => `
      <li class="nav-item">
        <a class="nav-link edzai-tab-link ${i === 0 ? "active" : ""}"
           href="#" data-tabid="${t.id}" role="tab"
           aria-selected="${i === 0 ? "true" : "false"}">${escHtml(t.label)}</a>
      </li>`).join("");

    const tabPanes = tabs.map((t, i) => `
      <div class="tab-pane fade ${i === 0 ? "show active" : ""}"
           id="edzai-tab-${t.id}" role="tabpanel">
        ${t.content}
      </div>`).join("");

    return `
      <div class="edzai-edit-modal">
        <ul class="nav nav-tabs edzai-edit-tabs mb-3" role="tablist">${tabHeaders}</ul>
        <div class="tab-content">${tabPanes}</div>
        <div class="edzai-edit-footer mt-3 d-flex justify-content-between align-items-center">
          <span id="edzai-save-status" class="small text-muted"></span>
          <button class="btn btn-primary edzai-save-btn" id="edzai-save-edits">Save changes</button>
        </div>
      </div>`;
};

// ── Modal tab + row interactions ───────────────────────────────────────────

const bindModalTabs = (root) => {
    // JS tab switching — cross-version safe (BS4 + BS5)
    root.querySelectorAll(".edzai-tab-link").forEach((link) => {
        link.addEventListener("click", (e) => {
            e.preventDefault();
            const tabId = link.dataset.tabid;
            root.querySelectorAll(".edzai-tab-link").forEach((l) => {
                l.classList.remove("active");
                l.setAttribute("aria-selected", "false");
            });
            root.querySelectorAll(".tab-pane").forEach((p) => p.classList.remove("show", "active"));
            link.classList.add("active");
            link.setAttribute("aria-selected", "true");
            const pane = root.querySelector("#edzai-tab-" + tabId);
            if (pane) {
                pane.classList.add("show", "active");
            }
        });
    });

    // Glossary add/remove
    root.querySelector("#edzai-gloss-add")?.addEventListener("click", () => {
        const list = root.querySelector("#edzai-glossary-list");
        const row = document.createElement("div");
        row.className = "edzai-glossary-row mb-2";
        row.innerHTML = `
          <div class="d-flex align-items-start">
            <input type="text" class="form-control edzai-gloss-term mr-2"
                   placeholder="Term" style="flex:0 0 35%">
            <textarea class="form-control edzai-gloss-def mr-2" rows="2"
                      placeholder="Definition" style="flex:1"></textarea>
            <button class="btn btn-sm btn-outline-danger edzai-gloss-del flex-shrink-0">${ICON_TRASH}</button>
          </div>`;
        list.appendChild(row);
    });
    root.querySelector("#edzai-glossary-list")?.addEventListener("click", (e) => {
        if (e.target.closest(".edzai-gloss-del")) {
            e.target.closest(".edzai-glossary-row").remove();
        }
    });

    // Flashcard add/remove
    root.querySelector("#edzai-card-add")?.addEventListener("click", () => {
        const list = root.querySelector("#edzai-flashcards-list");
        const idx = list.querySelectorAll(".edzai-flashcard-row").length;
        const row = document.createElement("div");
        row.className = "edzai-flashcard-row border rounded p-2 mb-2";
        row.innerHTML = `
          <div class="d-flex justify-content-between align-items-center mb-1">
            <strong class="small text-muted">Card ${idx + 1}</strong>
            <button class="btn btn-sm btn-outline-danger edzai-card-del">${ICON_TRASH}</button>
          </div>
          <div class="form-group mb-1">
            <label class="small font-weight-bold">Front</label>
            <textarea class="form-control edzai-card-front" rows="2"></textarea>
          </div>
          <div class="form-group mb-0">
            <label class="small font-weight-bold">Back</label>
            <textarea class="form-control edzai-card-back" rows="2"></textarea>
          </div>`;
        list.appendChild(row);
    });
    root.querySelector("#edzai-flashcards-list")?.addEventListener("click", (e) => {
        if (e.target.closest(".edzai-card-del")) {
            e.target.closest(".edzai-flashcard-row").remove();
        }
    });

    // FAQ add/remove
    root.querySelector("#edzai-faq-add")?.addEventListener("click", () => {
        const list = root.querySelector("#edzai-faq-list");
        const idx = list.querySelectorAll(".edzai-faq-row").length;
        const row = document.createElement("div");
        row.className = "edzai-faq-row border rounded p-2 mb-2";
        row.innerHTML = `
          <div class="d-flex justify-content-between align-items-center mb-1">
            <strong class="small text-muted">Q${idx + 1}</strong>
            <button class="btn btn-sm btn-outline-danger edzai-faq-del">${ICON_TRASH}</button>
          </div>
          <div class="form-group mb-1">
            <label class="small font-weight-bold">Question</label>
            <textarea class="form-control edzai-faq-q" rows="2"></textarea>
          </div>
          <div class="form-group mb-0">
            <label class="small font-weight-bold">Answer</label>
            <textarea class="form-control edzai-faq-a" rows="3"></textarea>
          </div>`;
        list.appendChild(row);
    });
    root.querySelector("#edzai-faq-list")?.addEventListener("click", (e) => {
        if (e.target.closest(".edzai-faq-del")) {
            e.target.closest(".edzai-faq-row").remove();
        }
    });

    // Quiz add/remove
    root.querySelector("#edzai-q-add")?.addEventListener("click", () => {
        const list = root.querySelector("#edzai-quiz-list");
        const idx = list.querySelectorAll(".edzai-quiz-row").length;
        const row = document.createElement("div");
        row.className = "edzai-quiz-row border rounded p-2 mb-2";
        row.dataset.idx = idx;
        const optHtml = [0, 1, 2, 3].map((oi) => `
          <div class="d-flex align-items-center mb-1">
            <input type="radio" name="edzai-q${idx}-correct" value="${oi}" class="mr-2"
                   ${oi === 0 ? "checked" : ""}>
            <input type="text" class="form-control form-control-sm edzai-opt-text"
                   placeholder="Option ${oi + 1}">
          </div>`).join("");
        row.innerHTML = `
          <div class="d-flex justify-content-between align-items-center mb-1">
            <strong class="small text-muted">Q${idx + 1}</strong>
            <button class="btn btn-sm btn-outline-danger edzai-q-del">${ICON_TRASH}</button>
          </div>
          <div class="form-group mb-2">
            <label class="small font-weight-bold">Question</label>
            <textarea class="form-control edzai-q-text" rows="2"></textarea>
          </div>
          <div class="form-group mb-0">
            <label class="small font-weight-bold">Options</label>
            <div class="edzai-opts-list">${optHtml}</div>
          </div>`;
        list.appendChild(row);
    });
    root.querySelector("#edzai-quiz-list")?.addEventListener("click", (e) => {
        if (e.target.closest(".edzai-q-del")) {
            e.target.closest(".edzai-quiz-row").remove();
        }
    });
};

// ── Save handler ───────────────────────────────────────────────────────────

const bindModalSave = (root, cmid) => {
    const saveBtn = root.querySelector("#edzai-save-edits");
    const statusEl = root.querySelector("#edzai-save-status");
    if (!saveBtn) {
        return;
    }

    saveBtn.addEventListener("click", async() => {
        saveBtn.disabled = true;
        statusEl.textContent = "Saving\u2026";
        statusEl.style.color = "";

        const saves = [];

        const summaryEl = root.querySelector("#edzai-edit-summary");
        if (summaryEl) {
            saves.push(Repository.saveSummaryEdit(cmid, summaryEl.value)
                .catch((e) => Promise.reject(new Error("Summary: " + e.message))));
        }

        const glossRows = root.querySelectorAll("#edzai-glossary-list .edzai-glossary-row");
        if (glossRows.length) {
            const terms = Array.from(glossRows).map((row) => ({
                term: (row.querySelector(".edzai-gloss-term")?.value || "").trim(),
                definition: (row.querySelector(".edzai-gloss-def")?.value || "").trim(),
            })).filter((t) => t.term);
            saves.push(Repository.saveGlossaryEdit(cmid, JSON.stringify(terms))
                .catch((e) => Promise.reject(new Error("Glossary: " + e.message))));
        }

        const cardRows = root.querySelectorAll("#edzai-flashcards-list .edzai-flashcard-row");
        if (cardRows.length) {
            const cards = Array.from(cardRows).map((row) => ({
                front: (row.querySelector(".edzai-card-front")?.value || "").trim(),
                back: (row.querySelector(".edzai-card-back")?.value || "").trim(),
            })).filter((c) => c.front);
            saves.push(Repository.saveFlashcardsEdit(cmid, JSON.stringify(cards))
                .catch((e) => Promise.reject(new Error("Flashcards: " + e.message))));
        }

        const qRows = root.querySelectorAll("#edzai-quiz-list .edzai-quiz-row");
        if (qRows.length) {
            const questions = Array.from(qRows).map((row, qi) => {
                const options = Array.from(row.querySelectorAll(".edzai-opt-text"))
                    .map((o) => o.value.trim()).filter(Boolean);
                const checked = row.querySelector(`input[name="edzai-q${qi}-correct"]:checked`);
                return {
                    question: (row.querySelector(".edzai-q-text")?.value || "").trim(),
                    options,
                    correct_index: checked ? parseInt(checked.value, 10) : 0,
                    explanation: (row.querySelector(".edzai-q-expl")?.value || "").trim(),
                };
            }).filter((q) => q.question);
            saves.push(Repository.saveQuizEdit(cmid, JSON.stringify(questions))
                .catch((e) => Promise.reject(new Error("Quiz: " + e.message))));
        }

        const faqRows = root.querySelectorAll("#edzai-faq-list .edzai-faq-row");
        if (faqRows.length) {
            const faqs = Array.from(faqRows).map((row) => ({
                question: (row.querySelector(".edzai-faq-q")?.value || "").trim(),
                answer: (row.querySelector(".edzai-faq-a")?.value || "").trim(),
            })).filter((f) => f.question);
            saves.push(Repository.saveFaqEdit(cmid, JSON.stringify(faqs))
                .catch((e) => Promise.reject(new Error("FAQ: " + e.message))));
        }

        try {
            await Promise.all(saves);
            statusEl.textContent = "\u2713 Saved successfully";
            statusEl.style.color = "green";
            window.setTimeout(() => {
                statusEl.textContent = "";
                statusEl.style.color = "";
            }, 3000);
        } catch (err) {
            statusEl.textContent = "\u2717 " + (err.message || "Save failed");
            statusEl.style.color = "red";
        } finally {
            saveBtn.disabled = false;
        }
    });
};

// ── Utilities ──────────────────────────────────────────────────────────────

const safeParseJson = (val, fallback) => {
    try {
        return JSON.parse(val) || fallback;
    } catch (e) {
        return fallback;
    }
};

const escHtml = (str) =>
    String(str || "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
