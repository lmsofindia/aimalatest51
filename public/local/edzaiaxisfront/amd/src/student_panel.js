// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Student AI panel — injected on module view pages.
 *
 * Renders a tabbed panel with all available AI outputs:
 *  Summary | Glossary | Flashcards | Quiz | FAQ | Infographic | Transcript | Study Chat
 *
 * Note: KB/Support chat is intentionally excluded from the student panel on content
 * pages. Support chat is available in the global floating chatbot instead.
 *
 * @module     local_edzaiaxisfront/student_panel
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Repository from 'local_edzaiaxisfront/repository';
import Notification from 'core/notification'; // eslint-disable-line no-unused-vars
  let cfg = {};
  let outputs = {};
  let chat = { sessionId: null, mode: null };

  // Feather-style line icons (inherit currentColor), replacing the old emoji.
  const svgIcon = (inner) =>
    `<svg class="edzai-tab-ic" viewBox="0 0 24 24" width="15" height="15" fill="none" ` +
    `stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${inner}</svg>`;

  const TAB_ICONS = {
    summary: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/>',
    glossary: '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
    flashcards: '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>',
    quiz: '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
    faq: '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8z"/>',
    infographic: '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
    transcript: '<rect x="2" y="2" width="20" height="20" rx="2.5"/><line x1="7.5" y1="2" x2="7.5" y2="22"/><line x1="16.5" y1="2" x2="16.5" y2="22"/><line x1="2" y1="12" x2="22" y2="12"/>',
    chatbot: '<path d="M12 3l1.9 4.1L18 9l-4.1 1.9L12 15l-1.9-4.1L6 9l4.1-1.9z"/><path d="M5 16l.9 1.9L8 19l-2.1.9L5 22l-.9-2.1L2 19l2.1-1.1z"/>',
  };
  const TAB_LABEL_TEXT = {
    summary: "Summary", glossary: "Glossary", flashcards: "Flashcards", quiz: "Quiz",
    faq: "FAQ", infographic: "Infographic", transcript: "Transcript", chatbot: "Study Chat",
  };
  const tabLabel = (f) =>
    `${svgIcon(TAB_ICONS[f] || "")}<span class="edzai-tab-txt">${TAB_LABEL_TEXT[f] || f}</span>`;

  const ICON_CHEV_L = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>';
  const ICON_CHEV_R = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';

  const init = async (panelCfg) => {
    // Guard: prevent double-init if both after_config and before_footer fire
    if (document.getElementById("edzai-student-panel")) {
      return;
    }

    cfg = panelCfg;

    try {
      outputs = await Repository.getCmOutputs(cfg.cmid);
    } catch (e) {
      return;
    }

    if (!outputs.available_features) {
      outputs.available_features = [];
    }

    // Always offer Study Chat on content pages (injected regardless of generated features)
    if (!outputs.available_features.includes("chatbot")) {
      outputs.available_features.push("chatbot");
    }

    // Remove kb_chat — Support chat belongs in the global floating chatbot, not here
    outputs.available_features = outputs.available_features.filter(
      (f) => f !== "kb_chat",
    );

    if (!outputs.available_features.length) {
      return;
    }

    // Lead with Summary, keep Study Chat last; everything else in a stable order.
    const ORDER = ["summary", "glossary", "flashcards", "quiz", "faq", "infographic", "transcript", "chatbot"];
    outputs.available_features.sort(
      (a, b) => (ORDER.indexOf(a) + 1 || 99) - (ORDER.indexOf(b) + 1 || 99),
    );

    injectPanel();
    injectHeaderLauncher();

    // Auto-open the drawer when the learner arrived from a course-page AI badge
    // (link ends in #ai). Deferred a tick so the panel DOM exists first.
    if (window.location.hash === "#ai") {
      window.setTimeout(openDrawer, 250);
    }
  };

  // ── Header launcher button + drawer open/close ─────────────────────────────
  // Replaces the old bottom-left floating "Study with AI" button. Places a small
  // launcher in the top navbar (edzcorp theme: .navbar-right) with fallbacks, and
  // toggles the right slide-in drawer.
  function injectHeaderLauncher() {
    if (document.getElementById("edzai-launch-btn")) {
      return;
    }
    const btn = document.createElement("button");
    btn.id = "edzai-launch-btn";
    btn.type = "button";
    btn.className = "edzai-launch-btn";
    btn.title = "AI Learning Assistant";
    btn.setAttribute("aria-label", "Open AI Learning Assistant");
    btn.innerHTML =
      `<span class="edzai-launch-icon">✦</span>` +
      `<span class="edzai-launch-text">AI Assistant</span>`;

    const nav = document.querySelector(
      ".navbar-right, .navbar .usernavigation, #usernavigation, .navbar-nav.ms-auto, .navbar-nav.ml-auto",
    );
    const header = document.querySelector(
      "#page-header .page-context-header, .page-header-headings",
    );
    if (nav) {
      btn.classList.add("edzai-launch-btn--nav");
      nav.insertAdjacentElement("afterbegin", btn);
    } else if (header) {
      btn.classList.add("edzai-launch-btn--header");
      header.appendChild(btn);
    } else {
      btn.classList.add("edzai-launch-btn--float");
      document.body.appendChild(btn);
    }
    btn.addEventListener("click", toggleDrawer);
  }

  const openDrawer = () => {
    const p = document.getElementById("edzai-student-panel");
    const b = document.getElementById("edzai-drawer-backdrop");
    if (p) { p.classList.add("edzai-open"); }
    if (b) { b.classList.add("edzai-open"); }
    const l = document.getElementById("edzai-launch-btn");
    if (l) { l.classList.add("edzai-active"); }
    document.body.classList.add("edzai-drawer-lock");
  };

  const closeDrawer = () => {
    const p = document.getElementById("edzai-student-panel");
    const b = document.getElementById("edzai-drawer-backdrop");
    if (p) { p.classList.remove("edzai-open"); }
    if (b) { b.classList.remove("edzai-open"); }
    const l = document.getElementById("edzai-launch-btn");
    if (l) { l.classList.remove("edzai-active"); }
    document.body.classList.remove("edzai-drawer-lock");
  };

  const toggleDrawer = () => {
    const p = document.getElementById("edzai-student-panel");
    if (p && p.classList.contains("edzai-open")) {
      closeDrawer();
    } else {
      openDrawer();
    }
  };

  // ── Panel injection (right slide-in drawer) ────────────────────────────────
  const injectPanel = () => {
    // Dismiss backdrop
    const backdrop = document.createElement("div");
    backdrop.id = "edzai-drawer-backdrop";
    backdrop.className = "edzai-drawer-backdrop";
    backdrop.addEventListener("click", closeDrawer);
    document.body.appendChild(backdrop);

    // Drawer
    const panel = document.createElement("div");
    panel.id = "edzai-student-panel";
    panel.className = "edzai-student-panel edzai-drawer";
    panel.setAttribute("role", "dialog");
    panel.setAttribute("aria-label", "AI Learning Assistant");
    panel.innerHTML = buildPanelHTML();

    document.body.appendChild(panel);
    attachPanelEvents(panel);
    activateFirstTab(panel);
  };

  // ── Panel HTML ─────────────────────────────────────────────────────────────
  const buildPanelHTML = () => {
    const feats = outputs.available_features;

    const tabHeaders = feats
      .map(
        (f, i) => `
    <li class="edzai-tab-item">
        <button class="edzai-tab-btn ${i === 0 ? "active" : ""}" data-tab="${f}">
            ${tabLabel(f)}
        </button>
    </li>`,
      )
      .join("");

    const tabPanels = feats
      .map(
        (f, i) => `
    <div class="edzai-tab-pane ${i === 0 ? "active" : ""}" data-tab="${f}">
        ${buildTabContent(f)}
    </div>`,
      )
      .join("");

    return `
    <div class="edzai-panel-header">
        <div class="edzai-panel-titlebar">
            <div class="edzai-panel-title">
                <span class="edzai-panel-icon">✦</span>
                AI Learning Assistant
            </div>
            <button type="button" class="edzai-drawer-close" id="edzai-drawer-close" aria-label="Close">✕</button>
        </div>
        <ul class="edzai-tab-nav">${tabHeaders}</ul>
    </div>
    <div class="edzai-panel-body">
        ${tabPanels}
    </div>`;
  };

  const buildTabContent = (feature) => {
    switch (feature) {
      case "summary":
        return buildSummary(outputs.summary);
      case "faq":
        return buildFaq(outputs.faq);
      case "infographic":
        return buildInfographic(outputs.infographic);
      case "glossary":
        return buildGlossary(outputs.glossary);
      case "flashcards":
        return buildFlashcards(outputs.flashcards);
      case "quiz":
        return buildQuiz(outputs.quiz);
      case "transcript":
        return buildTranscript(outputs.transcript);
      case "chatbot":
        return buildChat("study");
      default:
        return "";
    }
  };

  // ── Tab content builders ───────────────────────────────────────────────────

  const buildSummary = (str) => {
    if (!str) {
      return emptyState("No summary available yet.");
    }
    return `<div class="edzai-summary-content">${escHtml(str).replace(/\n/g, "<br>")}</div>`;
  };

  const buildFaq = (jsonStr) => {
    const items = safeJsonParse(jsonStr, []);
    if (!items.length) {
      return emptyState("No FAQ available yet.");
    }

    const accordionItems = items
      .map(
        (item, i) => `
    <div class="edzai-faq-item">
        <button class="edzai-faq-question" data-faq="${i}">
            <span>${escHtml(item.question || item.q || "")}</span>
            <span class="edzai-faq-chevron">▼</span>
        </button>
        <div class="edzai-faq-answer" id="edzai-faq-${i}" style="display:none">
            <p>${escHtml(item.answer || item.a || "")}</p>
        </div>
    </div>`,
      )
      .join("");

    return `<div class="edzai-faq">${accordionItems}</div>`;
  };

  const buildGlossary = (jsonStr) => {
    const terms = safeJsonParse(jsonStr, []);
    if (!terms.length) {
      return emptyState("No glossary terms available yet.");
    }

    const rows = terms
      .map(
        (t) => `
    <div class="edzai-glossary-term">
        <dt>${escHtml(t.term)}</dt>
        <dd>${escHtml(t.definition)}${t.context_note ? `<em class="edzai-context-note"> — ${escHtml(t.context_note)}</em>` : ""}</dd>
    </div>`,
      )
      .join("");

    return `<dl class="edzai-glossary">${rows}</dl>`;
  };

  const buildFlashcards = (jsonStr) => {
    const cards = safeJsonParse(jsonStr, []);
    if (!cards.length) {
      return emptyState("No flashcards available yet.");
    }

    return `
    <div class="edzai-flashcard-deck">
        <div class="edzai-flashcard" id="edzai-fc-current" tabindex="0" title="Click to flip" role="button" aria-label="Flashcard — click to flip">
            <div class="edzai-fc-inner">
                <div class="edzai-fc-face edzai-fc-front">
                    <div class="edzai-fc-label">Question</div>
                    <p class="edzai-fc-text">${escHtml(cards[0].front)}</p>
                    <span class="edzai-fc-hint">Tap to flip</span>
                </div>
                <div class="edzai-fc-face edzai-fc-back">
                    <div class="edzai-fc-label">Answer</div>
                    <p class="edzai-fc-text">${escHtml(cards[0].back)}</p>
                    <span class="edzai-fc-hint">Tap to flip back</span>
                </div>
            </div>
        </div>
        <div class="edzai-fc-controls">
            <button class="edzai-fc-nav" id="edzai-fc-prev" aria-label="Previous card">${ICON_CHEV_L}</button>
            <span class="edzai-fc-counter" id="edzai-fc-counter">1 / ${cards.length}</span>
            <button class="edzai-fc-nav" id="edzai-fc-next" aria-label="Next card">${ICON_CHEV_R}</button>
        </div>
    </div>
    <script type="application/json" id="edzai-fc-data">${JSON.stringify(cards)}<\/script>`;
  };

  const buildQuiz = (jsonStr) => {
    const questions = safeJsonParse(jsonStr, []);
    if (!questions.length) {
      return emptyState("No quiz questions available yet.");
    }

    const qHtml = questions
      .map((q, i) => {
        const options = safeJsonParse(q.options_json, []);
        const optHtml = options
          .map(
            (opt, j) => `
        <label class="edzai-quiz-option">
            <input type="radio" name="edzai-q-${i}" value="${j}">
            <span>${escHtml(opt)}</span>
        </label>`,
          )
          .join("");

        return `
        <div class="edzai-quiz-question" data-index="${i}" data-answer="${escHtml(q.answer)}">
            <p class="edzai-quiz-qtext">${i + 1}. ${escHtml(q.question_text)}</p>
            ${
              optHtml
                ? `<div class="edzai-quiz-options">${optHtml}</div>`
                : `<input type="text" class="edzai-input edzai-quiz-freetext" placeholder="Your answer…">`
            }
            <div class="edzai-quiz-feedback" style="display:none"></div>
            <button class="edzai-btn-secondary edzai-quiz-check mt-2">Check Answer</button>
        </div>`;
      })
      .join("");

    return `<div class="edzai-quiz">${qHtml}</div>`;
  };

  const buildInfographic = (content) => {
    if (!content) {
      return emptyState("No infographic available yet.");
    }
    // Render inside an iframe so that <style>body{...}</style> inside the
    // infographic HTML does not bleed into the Moodle page stylesheet.
    // srcdoc creates a fully isolated document with its own <body>.
    const srcdoc = content.replace(/"/g, "&quot;");
    return `<iframe class="edzai-infographic-frame"
      srcdoc="${srcdoc}"
      sandbox="allow-same-origin"
      style="width:100%;min-height:600px;border:none;display:block;"
      title="Infographic"
      onload="try{this.style.height=(this.contentDocument.body.scrollHeight+40)+'px';}catch(e){}"></iframe>`;
  };

  const buildTranscript = (jsonStr) => {
    const segments = safeJsonParse(jsonStr, []);
    if (!segments.length) {
      return emptyState("No transcript available yet.");
    }

    const rows = segments
      .map((seg) => {
        const timestamp = seg.timestamp || seg.start || seg.time || "";
        const title = seg.title || "";
        const text = seg.text || seg.content || "";
        const tsFormatted = timestamp ? formatTimestamp(timestamp) : "";

        if (title) {
          return `
          <div class="edzai-transcript-chapter">
              <h6 class="edzai-transcript-chapter-title">
                  ${tsFormatted ? `<span class="edzai-ts-badge">${escHtml(tsFormatted)}</span>` : ""}
                  ${escHtml(title)}
              </h6>
              ${text ? `<p class="edzai-transcript-text">${escHtml(text)}</p>` : ""}
          </div>`;
        }
        return `
        <div class="edzai-transcript-segment">
            ${tsFormatted ? `<span class="edzai-ts-badge">${escHtml(tsFormatted)}</span>` : ""}
            <span class="edzai-transcript-text">${escHtml(text)}</span>
        </div>`;
      })
      .join("");

    return `<div class="edzai-transcript">${rows}</div>`;
  };

  const buildChat = (mode) => {
    return `
    <div class="edzai-chat" data-mode="${mode}">
        <div class="edzai-chat-messages" id="edzai-chat-msgs-${mode}" role="log" aria-live="polite"></div>
        <div class="edzai-chat-status" id="edzai-chat-status-${mode}"></div>
        <div class="edzai-chat-input-row">
            <textarea class="edzai-chat-input" rows="1"
                   id="edzai-chat-input-${mode}" data-mode="${mode}"
                   placeholder="Ask about this content…"></textarea>
            <button class="edzai-btn-primary edzai-chat-send" data-mode="${mode}" title="Send">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
        </div>
    </div>`;
  };

  // ── Event handlers ─────────────────────────────────────────────────────────
  const attachPanelEvents = (panel) => {
    // Drawer close (× button)
    panel.addEventListener("click", (e) => {
      if (e.target.closest("#edzai-drawer-close")) {
        closeDrawer();
      }
    });
    // Esc closes the drawer
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        closeDrawer();
      }
    });

    // Tab switching
    panel.addEventListener("click", (e) => {
      const btn = e.target.closest(".edzai-tab-btn");
      if (btn) {
        activateTab(panel, btn.dataset.tab);
      }
    });

    // FAQ accordion
    panel.addEventListener("click", (e) => {
      const btn = e.target.closest(".edzai-faq-question");
      if (!btn) {
        return;
      }
      const idx = btn.dataset.faq;
      const answer = panel.querySelector("#edzai-faq-" + idx);
      const chev = btn.querySelector(".edzai-faq-chevron");
      if (!answer) {
        return;
      }
      const open = answer.style.display !== "none";
      answer.style.display = open ? "none" : "block";
      btn.classList.toggle("open", !open);
      if (chev) {
        chev.textContent = open ? "▼" : "▲";
      }
    });

    // Flashcard flip (3D)
    panel.addEventListener("click", (e) => {
      const card = e.target.closest("#edzai-fc-current");
      if (card) {
        card.classList.toggle("flipped");
      }
    });

    // Flashcard nav
    let fcIndex = 0;
    const fcNav = (delta) => {
      const dataEl = panel.querySelector("#edzai-fc-data");
      if (!dataEl) {
        return;
      }
      const cards = JSON.parse(dataEl.textContent);
      fcIndex = Math.max(0, Math.min(cards.length - 1, fcIndex + delta));
      const front = panel.querySelector(".edzai-fc-front .edzai-fc-text");
      const back = panel.querySelector(".edzai-fc-back .edzai-fc-text");
      const ctr = panel.querySelector("#edzai-fc-counter");
      if (front) {
        front.textContent = cards[fcIndex].front;
      }
      if (back) {
        back.textContent = cards[fcIndex].back;
      }
      if (ctr) {
        ctr.textContent = (fcIndex + 1) + " / " + cards.length;
      }
      // flip back to the question face when navigating
      const card = panel.querySelector("#edzai-fc-current");
      if (card) {
        card.classList.remove("flipped");
      }
    };
    panel.addEventListener("click", (e) => {
      if (e.target.closest("#edzai-fc-prev")) {
        fcNav(-1);
      }
      if (e.target.closest("#edzai-fc-next")) {
        fcNav(+1);
      }
    });

    // Quiz check answer
    panel.addEventListener("click", (e) => {
      const btn = e.target.closest(".edzai-quiz-check");
      if (!btn) {
        return;
      }
      const qDiv = btn.closest(".edzai-quiz-question");
      const answer = qDiv.dataset.answer;
      const selected = qDiv.querySelector(".edzai-quiz-option input:checked");
      const freetext = qDiv.querySelector(".edzai-quiz-freetext");
      const feedback = qDiv.querySelector(".edzai-quiz-feedback");

      const userAnswer = selected
        ? selected
            .closest(".edzai-quiz-option")
            .querySelector("span")
            .textContent.trim()
        : freetext
          ? freetext.value.trim()
          : "";

      const correct = userAnswer.toLowerCase() === answer.toLowerCase();
      feedback.className = `edzai-quiz-feedback edzai-quiz-feedback--${correct ? "correct" : "wrong"}`;
      feedback.textContent = correct
        ? "Correct!"
        : `Correct answer: ${answer}`;
      feedback.style.display = "flex";
    });

    // Chat send via button
    panel.addEventListener("click", async (e) => {
      const btn = e.target.closest(".edzai-chat-send");
      if (!btn) {
        return;
      }
      await handleChatSend(btn.dataset.mode, panel);
    });

    // Chat send via Enter
    panel.addEventListener("keydown", async (e) => {
      if (e.key === "Enter") {
        const input = e.target.closest(".edzai-chat-input");
        if (input) {
          await handleChatSend(input.dataset.mode, panel);
        }
      }
    });
  };

  // ── Local-intent shortcut ─────────────────────────────────────────────────
  // For messages that map to a known intent (summary, flashcards, etc.) we
  // serve directly from the already-loaded Moodle outputs rather than sending
  // a full RAG chat request.  This avoids the incomplete-chunk problem and is
  // instant — no LLM call needed.

  // Pagination state — resets each time a fresh intent is triggered.
  let flashcardOffset = 0;
  let glossaryOffset = 0;

  // Build styled flashcard HTML cards from a slice of card data.
  const buildFlashcardHtml = (batch, startIdx) =>
    `<div class="edzai-chat-cards">${batch
      .map(
        (c, i) =>
          `<div class="edzai-chat-card">
        <div class="edzai-chat-card-num">Card ${startIdx + i + 1}</div>
        <div class="edzai-chat-card-q"><strong>Q:</strong> ${escHtml(c.front || c.question || "")}</div>
        <div class="edzai-chat-card-a"><strong>A:</strong> ${escHtml(c.back || c.answer || "")}</div>
      </div>`,
      )
      .join("")}</div>`;

  const tryServeFromOutputs = (message, msgBox) => {
    const m = message.toLowerCase();

    // ── "Show more" pagination (check BEFORE fresh-intent matchers) ──────────

    if (/\bshow\s+more\s+flash/.test(m)) {
      const cards = safeJsonParse(outputs.flashcards, []);
      if (!cards.length || flashcardOffset >= cards.length) {
        appendChatMessage(
          msgBox,
          "ai",
          "No more flashcards — that's all of them!",
        );
        return true;
      }
      const batch = cards.slice(flashcardOffset, flashcardOffset + 5);
      const html = buildFlashcardHtml(batch, flashcardOffset);
      flashcardOffset += batch.length;
      const remaining = cards.length - flashcardOffset;
      const footer =
        remaining > 0
          ? `<p class="edzai-chat-cards-more"><em>${remaining} more — ask me "show more flashcards".</em></p>`
          : `<p class="edzai-chat-cards-more"><em>That's all ${cards.length} flashcards!</em></p>`;
      appendChatMessage(
        msgBox,
        "ai-html",
        `<p>Here are more flashcards:</p>${html}${footer}`,
      );
      return true;
    }

    if (/\bshow\s+more\s+(key\s*term|glossary|term)/.test(m)) {
      const terms = safeJsonParse(outputs.glossary, []);
      if (!terms.length || glossaryOffset >= terms.length) {
        appendChatMessage(msgBox, "ai", "No more terms — that's all of them!");
        return true;
      }
      const batch = terms.slice(glossaryOffset, glossaryOffset + 6);
      const body = batch
        .map(
          (t) =>
            `<p class="edzai-chat-term"><strong>${escHtml(t.term)}:</strong> ${escHtml(t.definition)}</p>`,
        )
        .join("");
      glossaryOffset += batch.length;
      const remaining = terms.length - glossaryOffset;
      const footer =
        remaining > 0
          ? `<p class="edzai-chat-cards-more"><em>${remaining} more — ask me "show more key terms".</em></p>`
          : `<p class="edzai-chat-cards-more"><em>That's all ${terms.length} key terms!</em></p>`;
      appendChatMessage(
        msgBox,
        "ai-html",
        `<p>More key terms:</p>${body}${footer}`,
      );
      return true;
    }

    // ── Fresh intents (reset pagination offsets) ──────────────────────────────

    // Summary
    if (/\bsummar(y|iz|is)\b/.test(m) && outputs.summary) {
      appendChatMessage(
        msgBox,
        "ai",
        `Here's a summary of this content:\n\n${outputs.summary}`,
      );
      return true;
    }

    // Flashcards (initial)
    if (/\bflashcard/.test(m)) {
      const cards = safeJsonParse(outputs.flashcards, []);
      if (cards.length) {
        const batch = cards.slice(0, 5);
        flashcardOffset = 5;
        const html = buildFlashcardHtml(batch, 0);
        const remaining = cards.length - 5;
        const footer =
          remaining > 0
            ? `<p class="edzai-chat-cards-more"><em>${remaining} more cards — ask me "show more flashcards".</em></p>`
            : "";
        appendChatMessage(
          msgBox,
          "ai-html",
          `<p>Here are flashcards for this content:</p>${html}${footer}`,
        );
        return true;
      }
    }

    // Glossary / key terms / key concepts (initial)
    if (/\b(glossary|key.?term|key.?concept|terminolog)/.test(m)) {
      const terms = safeJsonParse(outputs.glossary, []);
      if (terms.length) {
        const batch = terms.slice(0, 6);
        glossaryOffset = 6;
        const body = batch
          .map(
            (t) =>
              `<p class="edzai-chat-term"><strong>${escHtml(t.term)}:</strong> ${escHtml(t.definition)}</p>`,
          )
          .join("");
        const remaining = terms.length - 6;
        const footer =
          remaining > 0
            ? `<p class="edzai-chat-cards-more"><em>${remaining} more terms — ask me "show more key terms".</em></p>`
            : "";
        appendChatMessage(
          msgBox,
          "ai-html",
          `<p>Key terms for this content:</p>${body}${footer}`,
        );
        return true;
      }
    }

    // Quiz / test me
    if (/\b(quiz|test me|practice question)/.test(m)) {
      const questions = safeJsonParse(outputs.quiz, []);
      if (questions.length) {
        const show = questions.slice(0, 3);
        const body = show
          .map((q, i) => {
            const opts = safeJsonParse(q.options_json, []);
            const optText = opts.length
              ? "\n" +
                opts
                  .map((o, j) => `  ${String.fromCharCode(65 + j)}) ${o}`)
                  .join("\n")
              : "";
            return `**Q${i + 1}:** ${q.question_text}${optText}`;
          })
          .join("\n\n");
        appendChatMessage(
          msgBox,
          "ai",
          `Here are some practice questions:\n\n${body}\n\n*Switch to the **Quiz** tab to answer interactively and check your answers.*`,
        );
        return true;
      }
    }

    // FAQ
    if (/\b(faq|frequent|common.?question)/.test(m)) {
      const faqs = safeJsonParse(outputs.faq, []);
      if (faqs.length) {
        const show = faqs.slice(0, 4);
        const body = show
          .map(
            (f, i) =>
              `**Q${i + 1}:** ${f.question || f.q || ""}\n**A:** ${f.answer || f.a || ""}`,
          )
          .join("\n\n");
        appendChatMessage(
          msgBox,
          "ai",
          `Frequently asked questions:\n\n${body}`,
        );
        return true;
      }
    }

    // "Show me visually" / visual overview / diagram / infographic
    if (
      /\b(visual|diagram|infographic|overview|chart|at.a.glance|visually)\b/.test(
        m,
      )
    ) {
      const cards = safeJsonParse(outputs.flashcards, []);
      const terms = safeJsonParse(outputs.glossary, []);
      const questions = safeJsonParse(outputs.quiz, []);

      // Content stat chips
      const stats = [
        cards.length
          ? `<div class="edzai-vis-stat"><span class="edzai-vis-stat-val">${cards.length}</span><span class="edzai-vis-stat-lbl">🃏 Flashcards</span></div>`
          : "",
        questions.length
          ? `<div class="edzai-vis-stat"><span class="edzai-vis-stat-val">${questions.length}</span><span class="edzai-vis-stat-lbl">🎯 Quiz Qs</span></div>`
          : "",
        terms.length
          ? `<div class="edzai-vis-stat"><span class="edzai-vis-stat-val">${terms.length}</span><span class="edzai-vis-stat-lbl">📖 Key Terms</span></div>`
          : "",
      ]
        .filter(Boolean)
        .join("");

      // Parse objectives from HTML (line by line)
      const objHtml = outputs.objectives
        ? (() => {
            // Strip HTML tags and split by newlines / <li> / <br>
            const raw = outputs.objectives
              .replace(/<li[^>]*>/gi, "\n")
              .replace(/<[^>]+>/g, "")
              .split(/\n/)
              .map((s) => s.trim())
              .filter((s) => s.length > 4 && s.length < 300);
            if (!raw.length) {
              return "";
            }
            return `<div class="edzai-vis-section-title">🎓 Learning Objectives</div>
              <ol class="edzai-vis-objectives">
                ${raw
                  .slice(0, 6)
                  .map(
                    (o) =>
                      `<li class="edzai-vis-obj-item"><span class="edzai-vis-obj-num"></span>${escHtml(o)}</li>`,
                  )
                  .join("")}
              </ol>`;
          })()
        : "";

      // Key topic chips from glossary
      const termChips = terms
        .slice(0, 10)
        .map(
          (t) => `<span class="edzai-vis-term-chip">${escHtml(t.term)}</span>`,
        )
        .join("");
      const termHtml = termChips
        ? `<div class="edzai-vis-section-title">🏷️ Key Topics</div>
           <div class="edzai-vis-term-chips">${termChips}</div>`
        : "";

      // Quiz difficulty breakdown
      const diffCount = { easy: 0, medium: 0, hard: 0 };
      questions.forEach((q) => {
        if (diffCount[q.difficulty] !== undefined) {
          diffCount[q.difficulty]++;
        }
      });
      const diffTotal = diffCount.easy + diffCount.medium + diffCount.hard;
      const diffHtml =
        diffTotal > 0
          ? `<div class="edzai-vis-section-title">📊 Quiz Difficulty</div>
           <div class="edzai-vis-diff-bar">
             ${diffCount.easy ? `<div class="edzai-vis-diff-seg edzai-vis-diff--easy"   style="flex:${diffCount.easy}"   title="Easy: ${diffCount.easy}">Easy ${diffCount.easy}</div>` : ""}
             ${diffCount.medium ? `<div class="edzai-vis-diff-seg edzai-vis-diff--medium" style="flex:${diffCount.medium}" title="Medium: ${diffCount.medium}">Med ${diffCount.medium}</div>` : ""}
             ${diffCount.hard ? `<div class="edzai-vis-diff-seg edzai-vis-diff--hard"   style="flex:${diffCount.hard}"   title="Hard: ${diffCount.hard}">Hard ${diffCount.hard}</div>` : ""}
           </div>`
          : "";

      const hasContent = stats || objHtml || termHtml || diffHtml;
      if (!hasContent) {
        return false; // no visual data — fall through to RAG
      }

      const visHtml = `
        <div class="edzai-visual-overview">
          ${stats ? `<div class="edzai-vis-stats">${stats}</div>` : ""}
          ${objHtml}
          ${termHtml}
          ${diffHtml}
          <p class="edzai-vis-footer"><em>Switch to the tabs above for the full interactive experience.</em></p>
        </div>`;

      appendChatMessage(
        msgBox,
        "ai-html",
        `<p>Here's a visual overview of this content:</p>${visHtml}`,
      );
      return true;
    }

    return false; // not a local intent — fall through to RAG chat
  };

  const handleChatSend = async (mode, panel) => {
    const input = panel.querySelector(`#edzai-chat-input-${mode}`);
    const sendBtn = panel.querySelector(
      `.edzai-chat-send[data-mode="${mode}"]`,
    );
    const msgBox = panel.querySelector(`#edzai-chat-msgs-${mode}`);
    const status = panel.querySelector(`#edzai-chat-status-${mode}`);
    if (!input || !input.value.trim()) {
      return;
    }
    if (input.disabled) {
      return; // Already waiting for a response
    }

    const message = input.value.trim();
    input.value = "";

    appendChatMessage(msgBox, "user", message);

    // Serve from Moodle's pre-generated outputs if the intent matches —
    // faster, no tokens, and uses the full extracted text (not a RAG chunk).
    if (tryServeFromOutputs(message, msgBox)) {
      input.focus();
      return;
    }

    // Lock input while waiting for RAG response
    input.disabled = true;
    if (sendBtn) {
      sendBtn.disabled = true;
    }

    if (!chat.sessionId || chat.mode !== mode) {
      try {
        status.textContent = "Connecting\u2026";
        const res = await Repository.createChatSession(cfg.cmid, mode);
        chat.sessionId = res.session_id;
        chat.mode = mode;
        status.textContent = "";
      } catch (e) {
        status.textContent = "Could not connect. Please try again.";
        input.disabled = false;
        if (sendBtn) {
          sendBtn.disabled = false;
        }
        return;
      }
    }

    try {
      status.textContent = "Thinking\u2026";
      const res = await Repository.sendChatMessage(chat.sessionId, message);
      appendChatMessage(msgBox, "ai", res.reply);
      const sources = safeJsonParse(res.sources, []);
      if (sources.length) {
        const src = sources
          .map((s) => s.title || s.source || "")
          .filter(Boolean)
          .join(", ");
        appendChatMessage(msgBox, "sources", `Sources: ${src}`);
      }
      status.textContent = "";
    } catch (e) {
      status.textContent = "Error: " + (e.message || "Unknown error");
    } finally {
      // Always unlock input after response (or error)
      input.disabled = false;
      if (sendBtn) {
        sendBtn.disabled = false;
      }
      input.focus();
    }
  };

  // ── Markdown renderer (for AI messages) ───────────────────────────────────
  const applyMarkdown = (raw) =>
    raw
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/```([\s\S]*?)```/g, "<pre><code>$1</code></pre>")
      .replace(/`([^`]+)`/g, "<code>$1</code>")
      .replace(/\*\*\*(.*?)\*\*\*/g, "<strong><em>$1</em></strong>")
      .replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>")
      .replace(/\*(.*?)\*/g, "<em>$1</em>")
      .replace(/^\d+\.\s+(.+)$/gm, "<li>$1</li>")
      .replace(/^[-•]\s+(.+)$/gm, "<li>$1</li>")
      .replace(/(<li>.*<\/li>)/gs, "<ul>$1</ul>")
      .replace(/\n/g, "<br>");

  const appendChatMessage = (container, role, text) => {
    const div = document.createElement("div");
    div.className = `edzai-chat-msg edzai-chat-msg--${role}`;
    div.setAttribute("role", "listitem");

    if (role === "user") {
      div.innerHTML = `
        <div class="edzai-chat-msg-content">
          <div class="edzai-chat-msg-bubble">${escHtml(text)}</div>
        </div>
        <div class="edzai-chat-msg-avatar" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
        </div>`;
    } else if (role === "ai") {
      div.innerHTML = `
        <div class="edzai-chat-msg-avatar edzai-chat-msg-avatar--ai" aria-hidden="true">✦</div>
        <div class="edzai-chat-msg-content">
          <div class="edzai-chat-msg-bubble edzai-chat-msg-bubble--ai">${applyMarkdown(text)}</div>
        </div>`;
    } else if (role === "ai-html") {
      // Raw HTML content (flashcards, etc.) — caller is responsible for safe HTML
      div.className = "edzai-chat-msg edzai-chat-msg--ai";
      div.innerHTML = `
        <div class="edzai-chat-msg-avatar edzai-chat-msg-avatar--ai" aria-hidden="true">✦</div>
        <div class="edzai-chat-msg-content">
          <div class="edzai-chat-msg-bubble edzai-chat-msg-bubble--ai">${text}</div>
        </div>`;
    } else {
      div.innerHTML = `<div class="edzai-chat-msg-sources">${escHtml(text)}</div>`;
    }

    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
  };

  // ── Tab helpers ────────────────────────────────────────────────────────────
  const activateTab = (panel, key) => {
    panel
      .querySelectorAll(".edzai-tab-btn")
      .forEach((b) => b.classList.toggle("active", b.dataset.tab === key));
    panel
      .querySelectorAll(".edzai-tab-pane")
      .forEach((p) => p.classList.toggle("active", p.dataset.tab === key));
  };

  const activateFirstTab = (panel) => {
    const first = panel.querySelector(".edzai-tab-btn");
    if (first) {
      activateTab(panel, first.dataset.tab);
    }
  };

  // ── Utilities ──────────────────────────────────────────────────────────────
  const emptyState = (msg) =>
    `<div class="edzai-empty-state"><p>${escHtml(msg)}</p></div>`;

  const safeJsonParse = (str, fallback) => {
    try {
      return JSON.parse(str || "[]");
    } catch {
      return fallback;
    }
  };

  const escHtml = (str) =>
    String(str || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;");

  const formatTimestamp = (ts) => {
    if (typeof ts === "number") {
      const m = Math.floor(ts / 60);
      const s = Math.floor(ts % 60);
      return `${m}:${String(s).padStart(2, "0")}`;
    }
    return String(ts);
  };

export { init };
