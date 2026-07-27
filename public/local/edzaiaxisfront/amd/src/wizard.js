// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * 5-step AI generation wizard.
 *
 * State machine:
 *  step1 → select CMs (checkboxes)
 *  step2 → select output features (checkboxes filtered by site_features)
 *  step3 → configure parameters (language, Bloom's sliders, chunk size)
 *  step4 → review summary before submit
 *  step5 → job monitor (delegates to job_monitor.js)
 *
 * @module     local_edzaiaxisfront/wizard
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Repository from "local_edzaiaxisfront/repository";
import * as JobMonitor from "local_edzaiaxisfront/job_monitor";
import Notification from "core/notification";

let cfg = {};
let state = {
  step: 1,
  selected_cmids: [],
  selected_features: [],
  generation_config: {
    language: "",
    output_language: "",
    chunk_size: 1000,
    chunk_overlap: 200,
    bloom_distribution: {
      remember: 20,
      understand: 25,
      apply: 25,
      analyze: 15,
      evaluate: 10,
      create: 5,
    },
    quiz_count: 10,
    flashcard_count: 20,
    focus_areas: "",
  },
  cms: [], // full CM list from server
  jobs: [], // [{cmid, job_id, status, progress}]
};

export const init = (wizardCfg) => {
  cfg = wizardCfg;
  attachEvents();
  loadStep1();
};

// ── Step navigation ────────────────────────────────────────────────────────

const goTo = (step) => {
  state.step = step;
  updateStepIndicators(step);
  document.querySelectorAll(".edzai-step-panel").forEach((p) => {
    p.classList.toggle("d-none", parseInt(p.dataset.step) !== step);
  });
};

const updateStepIndicators = (active) => {
  document.querySelectorAll(".edzai-step-indicator").forEach((el) => {
    const n = parseInt(el.dataset.step);
    el.classList.toggle("active", n === active);
    el.classList.toggle("completed", n < active);
  });
};

// ── Step 1: Select CMs ────────────────────────────────────────────────────

const loadStep1 = async () => {
  const container = document.getElementById("edzai-step1-cms");
  if (!container) {
    return;
  }

  try {
    state.cms = await Repository.getCourseCms(cfg.courseid);
  } catch (e) {
    Notification.exception(e);
    return;
  }

  const rows = state.cms
    .map(
      (cm) => `
    <div class="form-check mb-2">
        <input class="form-check-input edzai-cm-check" type="checkbox"
               id="cm-${cm.cmid}" value="${cm.cmid}">
        <label class="form-check-label" for="cm-${cm.cmid}">
            <strong>${escHtml(cm.name)}</strong>
            <span class="text-muted ml-2">(${cm.modname} / ${cm.content_type})</span>
            <span class="badge badge-${statusBadge(cm.status)} ml-2">${cm.status}</span>
        </label>
    </div>`,
    )
    .join("");

  container.innerHTML =
    rows ||
    '<p class="text-muted">No supported activities found in this course.</p>';
  goTo(1);
};

// ── Step 2: Select Features ───────────────────────────────────────────────

const loadStep2 = () => {
  const container = document.getElementById("edzai-step2-features");
  if (!container) {
    return;
  }

  const generationFeatures = cfg.site_features.filter(
    (f) => !["chatbot", "kb_chat"].includes(f),
  );
  const featureLabels = {
    summary: "Summary",
    glossary: "Glossary",
    flashcards: "Flashcards",
    quiz: "Quiz Questions",
    faq: "FAQ",
    infographic: "Infographic",
  };

  const checks = generationFeatures
    .map(
      (f) => `
    <div class="form-check mb-2">
        <input class="form-check-input edzai-feature-check" type="checkbox"
               id="feat-${f}" value="${f}" checked>
        <label class="form-check-label" for="feat-${f}">${featureLabels[f] || f}</label>
    </div>`,
    )
    .join("");

  container.innerHTML =
    checks || '<p class="text-muted">No features enabled at site level.</p>';
  goTo(2);
};

// ── Step 3: Parameters ────────────────────────────────────────────────────

const loadStep3 = () => {
  // Bloom's sliders are already rendered in the Mustache template.
  // Just set default values and attach listeners.
  syncBloomsTotal();
  attachBloomsListeners();
  goTo(3);
};

const attachBloomsListeners = () => {
  document.querySelectorAll(".edzai-blooms-slider").forEach((slider) => {
    slider.addEventListener("input", () => {
      const key = slider.dataset.level;
      state.generation_config.bloom_distribution[key] = parseInt(slider.value);
      slider
        .closest(".edzai-blooms-row")
        .querySelector(".edzai-blooms-val").textContent = slider.value + "%";
      syncBloomsTotal();
    });
  });
};

const syncBloomsTotal = () => {
  const total = Object.values(
    state.generation_config.bloom_distribution,
  ).reduce((a, b) => a + b, 0);
  const bloomsTotal = document.getElementById("edzai-blooms-total");
  if (bloomsTotal) {
    bloomsTotal.textContent = total + "%";
    bloomsTotal.classList.toggle("text-danger", total !== 100);
    bloomsTotal.classList.toggle("text-success", total === 100);
  }
};

// ── Step 4: Review ────────────────────────────────────────────────────────

const loadStep4 = () => {
  const container = document.getElementById("edzai-step4-review");
  if (!container) {
    return;
  }

  const cmNames = state.selected_cmids
    .map((id) => {
      const cm = state.cms.find((c) => c.cmid === id);
      return cm ? `<li>${escHtml(cm.name)}</li>` : "";
    })
    .join("");

  const featList = state.selected_features.map((f) => `<li>${f}</li>`).join("");
  const gen = state.generation_config;

  container.innerHTML = `
    <h5>Activities to process (${state.selected_cmids.length})</h5>
    <ul>${cmNames}</ul>
    <h5>Features to generate</h5>
    <ul>${featList}</ul>
    <h5>Parameters</h5>
    <ul>
        <li>Language: ${gen.language || "auto-detect"}</li>
        <li>Output language: ${gen.output_language || "same as input"}</li>
        <li>Chunk size: ${gen.chunk_size}</li>
        <li>Quiz questions: ${gen.quiz_count}</li>
        <li>Flashcards: ${gen.flashcard_count}</li>
    </ul>`;

  goTo(4);
};

// ── Step 5: Submit + Monitor ──────────────────────────────────────────────

const submitAndMonitor = async () => {
  const submitBtn = document.getElementById("edzai-btn-submit");
  if (submitBtn) {
    submitBtn.disabled = true;
  }

  goTo(5);

  // Save wizard config for each selected CM, then submit ingest
  try {
    for (const cmid of state.selected_cmids) {
      await Repository.saveWizardConfig(
        cmid,
        state.selected_features,
        buildGenerationConfig(cmid),
      );
    }

    const results = await Repository.submitIngest(state.selected_cmids);
    state.jobs = results.map((r) => ({
      cmid: r.cmid,
      job_id: r.job_id,
      status: r.error ? "failed" : "queued",
      progress: 0,
      error: r.error || "",
    }));

    JobMonitor.start(state.jobs, {
      onUpdate: updateJobRow,
      onAllDone: () => {
        const hasError = state.jobs.some((j) => j.status === "failed");

        if (hasError) {
          return; // Some jobs failed — do not show success banner
        }

        const doneMsg = document.getElementById("edzai-step5-done");
        if (doneMsg) doneMsg.classList.remove("d-none");
      },
    });

    renderJobRows();
  } catch (e) {
    Notification.exception(e);
  }
};

const buildGenerationConfig = (cmid) => {
  const gen = state.generation_config;
  return {
    language: gen.language,
    output_language: gen.output_language,
    chunk_size: gen.chunk_size,
    chunk_overlap: gen.chunk_overlap,
    bloom_distribution: gen.bloom_distribution,
    quiz_count: gen.quiz_count,
    flashcard_count: gen.flashcard_count,
    focus_areas: gen.focus_areas,
  };
};

const renderJobRows = () => {
  const container = document.getElementById("edzai-step5-jobs");
  if (!container) {
    return;
  }

  container.innerHTML = state.jobs
    .map((job) => {
      const cm = state.cms.find((c) => c.cmid === job.cmid);
      const name = cm ? escHtml(cm.name) : `CM ${job.cmid}`;
      return `
        <div class="edzai-job-row mb-3" data-cmid="${job.cmid}">
            <div class="d-flex justify-content-between align-items-center">
                <strong>${name}</strong>
                <span class="edzai-job-status badge badge-secondary">${job.status}</span>
            </div>
            <div class="progress mt-1">
                <div class="progress-bar edzai-job-bar" role="progressbar"
                     style="width:${job.progress}%" aria-valuenow="${job.progress}"
                     aria-valuemin="0" aria-valuemax="100">${job.progress}%</div>
            </div>
            ${job.error ? `<small class="text-danger">${escHtml(job.error)}</small>` : ""}
        </div>`;
    })
    .join("");
};

const updateJobRow = (cmid, status, progress) => {
  // Keep state.jobs in sync so onAllDone's success/failure check is accurate —
  // otherwise a failed row shows 'failed' while the success banner still appears.
  const job = state.jobs.find((j) => j.cmid === cmid);
  if (job) {
    job.status = status;
    job.progress = progress;
  }

  const row = document.querySelector(`.edzai-job-row[data-cmid="${cmid}"]`);
  if (!row) {
    return;
  }

  const bar = row.querySelector(".edzai-job-bar");
  const badge = row.querySelector(".edzai-job-status");

  if (bar) {
    bar.style.width = progress + "%";
    bar.textContent = progress + "%";
  }
  if (badge) {
    badge.textContent = status;
    badge.className = "edzai-job-status badge badge-" + statusBadge(status);
  }
};

// ── Event wiring ──────────────────────────────────────────────────────────

const attachEvents = () => {
  const on = (id, event, fn) => {
    const el = document.getElementById(id);
    if (el) {
      el.addEventListener(event, fn);
    }
  };

  on("edzai-btn-step1-next", "click", () => {
    state.selected_cmids = [
      ...document.querySelectorAll(".edzai-cm-check:checked"),
    ].map((c) => parseInt(c.value));
    if (!state.selected_cmids.length) {
      // eslint-disable-next-line no-alert
      window.alert("Please select at least one activity.");
      return;
    }
    loadStep2();
  });

  on("edzai-btn-step2-back", "click", () => goTo(1));
  on("edzai-btn-step2-next", "click", () => {
    state.selected_features = [
      ...document.querySelectorAll(".edzai-feature-check:checked"),
    ].map((c) => c.value);
    if (!state.selected_features.length) {
      // eslint-disable-next-line no-alert
      window.alert("Please select at least one output feature.");
      return;
    }
    loadStep3();
  });

  on("edzai-btn-step3-back", "click", () => goTo(2));
  on("edzai-btn-step3-next", "click", () => {
    // Collect parameter values from form
    const g = state.generation_config;
    g.language = document.getElementById("edzai-language")?.value || "";
    g.output_language =
      document.getElementById("edzai-output-language")?.value || "";
    g.chunk_size = parseInt(
      document.getElementById("edzai-chunk-size")?.value || 1000,
    );
    g.chunk_overlap = parseInt(
      document.getElementById("edzai-chunk-overlap")?.value || 200,
    );
    g.quiz_count = parseInt(
      document.getElementById("edzai-quiz-count")?.value || 10,
    );
    g.flashcard_count = parseInt(
      document.getElementById("edzai-flashcard-count")?.value || 20,
    );
    g.focus_areas = document.getElementById("edzai-focus-areas")?.value || "";
    loadStep4();
  });

  on("edzai-btn-step4-back", "click", () => goTo(3));
  on("edzai-btn-submit", "click", submitAndMonitor);

  on("edzai-btn-step5-dashboard", "click", () => {
    window.location.href = cfg.dashboard_url;
  });
};

// ── Utilities ─────────────────────────────────────────────────────────────

const statusBadge = (status) =>
  ({
    not_ingested: "light",
    queued: "secondary",
    processing: "info",
    ready: "success",
    completed: "success",
    failed: "danger",
  })[status] || "secondary";

const escHtml = (str) =>
  String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
