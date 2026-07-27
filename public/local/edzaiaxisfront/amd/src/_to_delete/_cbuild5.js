// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Chatbot AMD module — main orchestrator.
 *
 * Manages:
 *  - Floating trigger open / close / minimise
 *  - Welcome panel (init data + stats)
 *  - Study mode  : left=courses list, middle=Learn/Practice chat, right=tips+streak
 *  - Support mode: left=thread history + new thread, middle=KB chat, right=tips
 *  - Analysis mode: left=upcoming events + recent threads, middle=4 metric cards
 *  - Right panel collapse / re-open
 *  - Chat session lifecycle (create → send → typing → render → end)
 *
 * All AJAX calls go through repository.js.
 *
 * @module     local_edzaiaxisfront/chatbot
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Chatbot AMD module — main orchestrator.
 *
 * Manages:
 *  - Floating trigger open / close / minimise
 *  - Welcome panel (init data + stats)
 *  - Study mode  : left=courses list, middle=Learn/Practice chat, right=tips+streak
 *  - Support mode: left=thread history + new thread, middle=KB chat, right=tips
 *  - Analysis mode: left=upcoming events + recent threads, middle=4 metric cards
 *  - Right panel collapse / re-open
 *  - Chat session lifecycle (create → send → typing → render → end)
 *
 * All AJAX calls go through repository.js.
 *
 * @module     local_edzaiaxisfront/chatbot
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["core/ajax"], function (Ajax) {
  // ── Repository helpers (inline — chatbot-specific functions) ─────────────────

  const call = (methodname, args = {}) => Ajax.call([{ methodname, args }])[0];

  const repo = {
    getInitData:         ()            => call("local_edzaiaxisfront_get_chatbot_init_data"),
    getEnrolledCourses:  ()            => call("local_edzaiaxisfront_get_enrolled_courses"),
    getAnalysisDashboard:()            => call("local_edzaiaxisfront_get_analysis_dashboard"),
    getUserAnalysis:     ()            => call("local_edzaiaxisfront_get_user_analysis"),
    getUpcomingEvents:   (limit = 8)   => call("local_edzaiaxisfront_get_upcoming_events", { limit }),
    getChatThreads:      (mode, limit) => call("local_edzaiaxisfront_get_chat_threads", { mode, limit }),
    getCourseAiCms:      (courseid)    => call("local_edzaiaxisfront_get_course_ai_cms", { courseid }),
    getCmOutputs:        (cmid)        => call("local_edzaiaxisfront_get_cm_outputs", { cmid }),
    createSession:       (cmid, mode)  => call("local_edzaiaxisfront_create_chat_session", { cmid, chat_mode: mode }),
    sendMessage:         (sid, msg)    => call("local_edzaiaxisfront_send_chat_message", { session_id: sid, message: msg }),
    endSession:          (sid)         => call("local_edzaiaxisfront_end_chat_session", { session_id: sid }),
    getSessionMessages:  (sid)         => call("local_edzaiaxisfront_get_session_messages", { session_id: sid }),
    renameThread:        (sid, name)   => call("local_edzaiaxisfront_rename_thread", { session_id: sid, name }),
    getSupportRightPanel:()            => call("local_edzaiaxisfront_get_support_right_panel"),
    getStudyActivityStream:(limit=12)  => call("local_edzaiaxisfront_get_study_activity_stream", { limit }),
    getTokenUsageSummary:()            => call("local_edzaiaxisfront_get_token_usage_summary"),
  };

  // ── State ────────────────────────────────────────────────────────────────────

  const state = {
    open: false,
    minimised: false,
    mode: null, // 'study' | 'support' | 'analysis' | null
    subtab: "learn", // 'learn' | 'practice'
    rightCollapsed: false,
    leftCollapsed: false,
    sessionId: null,
    currentCmid: null,
    currentCourse: null,
    waiting: false, // awaiting AI response
    config: {}, // AMD init config (userid, firstname, etc.)
    initData: null, // get_chatbot_init_data result
    courses: [], // enrolled courses with AI flag
    cmOutputs: null,       // pre-loaded AI outputs for the active CM (summary, flashcards, etc.)
    flashcardOffset: 0,   // next index to show for "show more flashcards"
    glossaryOffset: 0,    // next index to show for "show more key terms"
  };

  // ── DOM refs (populated in init) ─────────────────────────────────────────────

  let $root,
    $trigger,
    $backdrop,
    $panel,
    $header,
    $headerTitle,
    $headerSubtitle,
    $btnBack,
    $btnMinimise,
    $btnClose,
    $welcomeView,
    $welcomeSkeleton,
    $welcomeContent,
    $welcomeName,
    $welcomeTagline,
    $statCoursesVal,
    $statBadgesVal,
    $statStreakVal,
    $statPointsVal,
    $statPointsLbl,
    $threepanel,
    $leftInner,
    $subtabs,
    $chatArea,
    $messages,
    $placeholder,
    $placeholderText,
    $suggestionChips,
    $typing,
    $analysisArea,
    $analysisSkeleton,
    $analysisContent,
    $inputBar,
    $chatInput,
    $sendBtn,
    $charCount,
    $inputHint,
    $metricCoursesTotal,
    $metricCoursesSub,
    $metricCertsVal,
    $metricCertsSub,
    $metricPointsVal,
    $metricPointsLbl,
    $metricPointsSub,
    $metricProgressVal,
    $metricProgressFill,
    $analysisGrades,
    $gradeDisplay,
    $streakBanner,
    $streakText,
    $rightPanel,
    $rightInner,
    $leftPanel,
    $btnLeftToggle,
    $btnRightToggle,
    $placeholderIcon,
    $cmPickerArea;

  // ── Study encouragement content ──────────────────────────────────────────────

  const STUDY_TIPS = [
    {
      icon: "💡",
      title: "Active Recall",
      text: "Try to answer questions before checking answers — retrieval practice strengthens memory.",
    },
    {
      icon: "🎯",
      title: "Focus Mode",
      text: "Work in 25-minute sprints with 5-minute breaks for peak concentration.",
    },
    {
      icon: "🔗",
      title: "Make Connections",
      text: "Link new concepts to things you already know — associations make knowledge stick.",
    },
    {
      icon: "📝",
      title: "Take Notes",
      text: "Summarise key points in your own words. If you can explain it, you know it.",
    },
    {
      icon: "🌟",
      title: "Embrace Confusion",
      text: "Struggling with a topic is a sign you're learning. Push through the discomfort!",
    },
    {
      icon: "⏰",
      title: "Spaced Repetition",
      text: "Review material at increasing intervals: today, tomorrow, in a week, in a month.",
    },
  ];

  const SUPPORT_TIPS = [
    {
      icon: "🔍",
      title: "Be Specific",
      text: "The more specific your question, the better the answer. Include context!",
    },
    {
      icon: "📋",
      title: "Describe Steps",
      text: "Describe what you've already tried — this helps the AI give targeted guidance.",
    },
    {
      icon: "🧩",
      title: "Break It Down",
      text: "If a problem feels big, try asking about one small piece at a time.",
    },
  ];

  // ── Helpers ───────────────────────────────────────────────────────────────────

  const el = (id) => document.getElementById(id);
  const esc = (str) =>
    String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  const show = (elem) => {
    if (elem) {
      elem.hidden = false;
    }
  };
  const hide = (elem) => {
    if (elem) {
      elem.hidden = true;
    }
  };
  const setAttr = (elem, attr, val) => {
    if (elem) {
      elem.setAttribute(attr, val);
    }
  };

  function setHeaderMode(mode) {
    if (!$header) {
      return;
    }
    $header.classList.remove(
      "axis-header--study",
      "axis-header--support",
      "axis-header--analysis",
    );
    if (mode) {
      $header.classList.add("axis-header--" + mode);
    }
  }

  function setHeaderText(title, subtitle) {
    if ($headerTitle) {
      $headerTitle.textContent = title || "";
    }
    if ($headerSubtitle) {
      $headerSubtitle.textContent = subtitle || "";
    }
  }

  function scrollMessages() {
    if ($messages) {
      $messages.scrollTop = $messages.scrollHeight;
    }
  }

  function autoGrow(textarea) {
    textarea.style.height = "auto";
    textarea.style.height = Math.min(textarea.scrollHeight, 140) + "px";
  }

  // ── Render helpers ────────────────────────────────────────────────────────────

  // ── Markdown renderer ──────────────────────────────────────────────────────
  function applyMarkdown(raw) {
    return raw
      .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
      // Code blocks (```) — before inline code
      .replace(/```([\s\S]*?)```/g, "<pre><code>$1</code></pre>")
      // Inline code
      .replace(/`([^`]+)`/g, "<code>$1</code>")
      // Bold + italic combined
      .replace(/\*\*\*(.*?)\*\*\*/g, "<strong><em>$1</em></strong>")
      // Bold
      .replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>")
      // Italic
      .replace(/\*(.*?)\*/g, "<em>$1</em>")
      // Numbered lists
      .replace(/^\d+\.\s+(.+)$/gm, "<li>$1</li>")
      // Bullet lists
      .replace(/^[-•]\s+(.+)$/gm, "<li>$1</li>")
      // Wrap consecutive <li> in <ul>
      .replace(/(<li>.*<\/li>)/gs, "<ul>$1</ul>")
      // Line breaks (but not inside <pre>)
      .replace(/\n/g, "<br>");
  }

  function renderMessage(role, text) {
    const div = document.createElement("div");
    div.className = `axis-msg axis-msg--${role}`;
    div.setAttribute("role", "listitem");

    const time = new Date().toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });

    if (role === "user") {
      // User messages: plain text (escape only), right-aligned bubble
      const safeText = text.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;");
      div.innerHTML = `
        <div class="axis-msg-content">
          <div class="axis-msg-bubble">${safeText}</div>
          <div class="axis-msg-time">${time}</div>
        </div>
        <div class="axis-msg-avatar" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
        </div>`;
    } else if (role === "ai") {
      // AI messages: markdown rendered, left-aligned with avatar
      div.innerHTML = `
        <div class="axis-msg-avatar" aria-hidden="true">✦</div>
        <div class="axis-msg-content">
          <div class="axis-msg-bubble">${applyMarkdown(text)}</div>
          <div class="axis-msg-time">${time}</div>
        </div>`;
    } else if (role === "ai-html") {
      // AI messages with raw HTML (flashcards, etc.) — caller responsible for safe HTML
      div.className = "axis-msg axis-msg--ai";
      div.innerHTML = `
        <div class="axis-msg-avatar" aria-hidden="true">✦</div>
        <div class="axis-msg-content">
          <div class="axis-msg-bubble">${text}</div>
          <div class="axis-msg-time">${time}</div>
        </div>`;
    } else {
      // System / error messages
      const safeText = text.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;");
      div.innerHTML = `<div class="axis-msg-bubble axis-msg-bubble--system">${safeText}</div>`;
    }

    $messages.appendChild(div);
    scrollMessages();
  }

  function renderSuggestionChips(chips) {
    if (!$suggestionChips || !chips.length) {
      return;
    }
    $suggestionChips.innerHTML = chips
      .map(
        (c) =>
          `<button class="axis-chip" data-msg="${esc(c)}">${esc(c)}</button>`,
      )
      .join("");
    show($suggestionChips);
  }

  function clearChips() {
    if ($suggestionChips) {
      $suggestionChips.innerHTML = "";
      hide($suggestionChips);
    }
  }

  /**
   * Show a persistent "study context" chip bar after a local intent is served.
   * This lets the student navigate between content types without losing their place.
   * Called by tryLocalIntent after every successful local response.
   */
  function showStudyContextChips() {
    const outputs = state.cmOutputs;
    if (!outputs || !$suggestionChips) {
      return;
    }

    const available = [];
    if (outputs.summary) {
      available.push("Give me a summary");
    }
    if (safeParseJson(outputs.flashcards, []).length) {
      available.push("Show me some flashcards");
    }
    if (safeParseJson(outputs.glossary, []).length) {
      available.push("Show me key terms");
    }
    if (safeParseJson(outputs.quiz, []).length) {
      available.push("Quiz me");
    }
    if (outputs.summary || safeParseJson(outputs.glossary, []).length) {
      available.push("Show me visually");
    }

    if (!available.length) {
      return;
    }

    $suggestionChips.className = "axis-suggestion-chips axis-study-context-chips";
    $suggestionChips.innerHTML = available
      .map((c) => `<button class="axis-chip" data-msg="${esc(c)}">${esc(c)}</button>`)
      .join("");
    show($suggestionChips);
  }

  // ── Right panel content ───────────────────────────────────────────────────────

  function renderRightPanel(mode, course = null) {
    if (!$rightInner) {
      return;
    }

    const tips   = mode === "support" ? SUPPORT_TIPS : STUDY_TIPS;
    const tip    = tips[Math.floor(Math.random() * tips.length)];
    const streak = state.initData ? state.initData.streak_days : 0;

    const streakHtml = streak > 0
      ? `<div class="axis-right-streak">
           <span class="axis-right-streak-fire">🔥</span>
           <div>
             <div style="font-weight:700;font-size:13px">${streak}-day streak</div>
             <div style="font-size:10px;opacity:.75">Keep it up!</div>
           </div>
         </div>`
      : `<div class="axis-right-motivate">
           <div style="font-size:15px;margin-bottom:4px">🎯 Start your streak</div>
           <p class="axis-right-motivate-sub">Log in daily to build momentum.</p>
         </div>`;

    const courseHtml = course
      ? `<div class="axis-right-course-chip">
           <span class="axis-right-chip-label">Now studying</span>
           <strong>${esc(course.fullname || course.name || "")}</strong>
         </div>`
      : "";

    const tipHtml = `
      <div class="axis-right-tip">
          <div class="axis-right-tip-icon" style="font-size:18px;flex-shrink:0">${tip.icon}</div>
          <div class="axis-right-tip-body">
              <strong>${esc(tip.title)}</strong>
              <p>${esc(tip.text)}</p>
          </div>
      </div>`;

    const statsHtml = state.initData
      ? `<div class="axis-right-stats">
           <div class="axis-right-stat">
             <span class="axis-right-stat-num">${state.initData.course_count}</span>
             <span class="axis-right-stat-lbl">Courses</span>
           </div>
           <div class="axis-right-stat">
             <span class="axis-right-stat-num">${state.initData.badge_count}</span>
             <span class="axis-right-stat-lbl">Badges</span>
           </div>
           <div class="axis-right-stat">
             <span class="axis-right-stat-num">${state.initData.points}</span>
             <span class="axis-right-stat-lbl">${esc(state.initData.points_label)}</span>
           </div>
         </div>`
      : "";

    // Base render — mode-specific content loaded async below
    if (mode === "support") {
      $rightInner.innerHTML = `
        ${streakHtml}
        ${tipHtml}
        <div class="axis-right-section-title">📋 Knowledge Base Highlights</div>
        <div id="axis-right-faq-stream" class="axis-right-faq-stream">
          <div class="axis-right-loading">Loading FAQ…</div>
        </div>`;
      // Load FAQ async
      repo.getSupportRightPanel().then(data => {
        const faqs = safeParseJson(data.faqs_json, []);
        const faqEl = document.getElementById("axis-right-faq-stream");
        if (!faqEl) {
          return;
        }
        if (!faqs.length) {
          faqEl.innerHTML = '<p class="axis-right-empty">No FAQ items available yet.</p>';
          return;
        }
        faqEl.innerHTML = faqs.slice(0, 5).map(f => `
          <div class="axis-right-faq-item">
            <div class="axis-right-faq-q">${esc(f.q)}</div>
            <div class="axis-right-faq-a">${esc(f.a)}</div>
          </div>`).join("");
      }).catch(() => {
        const faqEl = document.getElementById("axis-right-faq-stream");
        if (faqEl) {
          faqEl.innerHTML = '';
        }
      });

    } else if (mode === "study") {
      $rightInner.innerHTML = `
        ${streakHtml}
        ${courseHtml}
        ${tipHtml}
        <div class="axis-right-section-title">📚 Your Study Activity</div>
        <div id="axis-right-activity-stream" class="axis-right-activity-stream">
          <div class="axis-right-loading">Loading…</div>
        </div>`;
      // Load activity stream async
      repo.getStudyActivityStream(8).then(data => {
        const events = safeParseJson(data.events_json, []);
        const el = document.getElementById("axis-right-activity-stream");
        if (!el) {
          return;
        }
        if (!events.length) {
          el.innerHTML = '<p class="axis-right-empty">No study sessions yet.</p>';
          return;
        }
        el.innerHTML = events.map(e => {
          const nameAttr = esc(e.cm_name).replace(/"/g, "&quot;");
          const openAttr = e.axis_session_id
            ? ` data-session="${esc(e.axis_session_id)}" data-cmid="${e.cmid}" data-name="${nameAttr}" role="button" tabindex="0" title="Reopen this session" style="cursor:pointer"`
            : "";
          return `
          <div class="axis-right-activity-item"${openAttr}>
            <div class="axis-right-activity-body">
              <div class="axis-right-activity-icon">📖</div>
              <div class="axis-right-activity-info">
                <div class="axis-right-activity-name">${esc(e.cm_name)}</div>
                ${e.course_name ? `<div class="axis-right-activity-course">${esc(e.course_name)}</div>` : ""}
                <div class="axis-right-activity-meta">
                  <span>${esc(e.time_label)}</span>
                  <span>${e.message_count} msg${e.message_count !== 1 ? "s" : ""}</span>
                </div>
              </div>
            </div>
          </div>`;
        }).join("");
      }).catch(() => {
        const el = document.getElementById("axis-right-activity-stream");
        if (el) {
          el.innerHTML = '';
        }
      });

    } else {
      // analysis mode handled by renderRightAnalysis
      $rightInner.innerHTML = `${streakHtml}${tipHtml}${statsHtml}`;
    }
  }

  function renderRightAnalysis(dashData) {
    if (!$rightInner) {
      return;
    }
    const streak = dashData ? dashData.streak_days : (state.initData ? state.initData.streak_days : 0);
    const level  = dashData ? dashData.points_level : 1;
    const pts    = dashData ? dashData.points.toLocaleString() : "—";
    const ptsLbl = dashData ? dashData.points_label : "Points";

    // Dynamic motivation tip (rotates by day of week)
    const tips = [
      { icon: "🧠", title: "Retrieval practice", text: "Quiz yourself daily — it's the #1 study technique proven by science." },
      { icon: "⏱️", title: "Focused sessions",   text: "25 minutes on, 5 minutes off. Short bursts beat marathon study." },
      { icon: "💬", title: "Ask differently",    text: "Ask the AI to explain concepts in a new way if something isn't clicking." },
      { icon: "🔁", title: "Spaced repetition",  text: "Revisit flashcards regularly to move knowledge to long-term memory." },
      { icon: "📖", title: "Active recall",       text: "Summarise each lesson in your own words for deeper understanding." },
    ];
    const tip = tips[new Date().getDay() % tips.length];

    const streakHtml = streak > 0
      ? `<div class="axis-right-streak">
           <span class="axis-right-streak-fire">🔥</span>
           <div>
             <div style="font-weight:700;font-size:13px">${streak}-day streak</div>
             <div style="font-size:10px;opacity:.75">Consistent learner — keep it up!</div>
           </div>
         </div>`
      : `<div class="axis-right-motivate">
           <div style="font-size:15px;margin-bottom:4px">🎯 Start your streak</div>
           <p class="axis-right-motivate-sub">Log in daily to build momentum.</p>
         </div>`;

    // Level + points card
    const levelHtml = `
      <div class="axis-right-level-card">
        <div class="axis-right-level-badge">Lv ${level}</div>
        <div class="axis-right-level-info">
          <div class="axis-right-level-pts">${pts}</div>
          <div class="axis-right-level-lbl">${esc(ptsLbl)}</div>
        </div>
      </div>`;

    $rightInner.innerHTML = `
        ${streakHtml}
        ${levelHtml}
        <div class="axis-right-tip">
            <div class="axis-right-tip-icon" style="font-size:18px;flex-shrink:0">${tip.icon}</div>
            <div class="axis-right-tip-body">
                <strong>${esc(tip.title)}</strong>
                <p>${esc(tip.text)}</p>
            </div>
        </div>
        <div class="axis-right-section-title">🪙 Token Usage</div>
        <div id="axis-right-token-card" class="axis-right-token-card">
          <div class="axis-right-loading">Loading…</div>
        </div>
        <div class="axis-right-footer-note">
            Knowledge is power 💪
        </div>
    `;

    // Load token usage async
    repo.getTokenUsageSummary().then(d => {
      const el = document.getElementById("axis-right-token-card");
      if (!el) {
        return;
      }

      const used    = d.today_tokens  || 0;
      const monthly = d.month_tokens  || 0;
      const limit   = d.monthly_limit || 0;
      const reset   = d.next_reset    || "";
      const pct     = d.pct_used >= 0 ? d.pct_used : (limit > 0 ? Math.min(100, Math.round((monthly / limit) * 100)) : 0);
      const warnCls = pct >= 80 ? " axis-right-token-bar-fill--warn" : "";

      // Format reset date nicely
      const resetLabel = reset ? new Date(reset).toLocaleDateString(undefined, {month: "short", day: "numeric"}) : "";

      el.innerHTML = `
        <div class="axis-right-token-row">
          <span class="axis-right-token-row-label">Today</span>
          <span class="axis-right-token-row-val">${used.toLocaleString()} tokens</span>
        </div>
        <div class="axis-right-token-row">
          <span class="axis-right-token-row-label">This month</span>
          <span class="axis-right-token-row-val">${monthly.toLocaleString()}</span>
        </div>
        ${limit > 0 ? `
        <div class="axis-right-token-bar-wrap">
          <div class="axis-right-token-bar-fill${warnCls}" style="width:${pct}%"></div>
        </div>
        <div class="axis-right-token-row">
          <span class="axis-right-token-row-label">Monthly limit</span>
          <span class="axis-right-token-row-val">${limit.toLocaleString()}</span>
        </div>
        <div class="axis-right-token-row">
          <span class="axis-right-token-row-label">Remaining</span>
          <span class="axis-right-token-row-val"
                style="color:${pct >= 80 ? '#d94040' : 'inherit'}">
            ${Math.max(0, limit - monthly).toLocaleString()}
          </span>
        </div>
        ${resetLabel ? `<div class="axis-right-token-reset">Resets ${esc(resetLabel)}</div>` : ""}
        ` : '<p class="axis-right-empty" style="margin:0">No usage limit set.</p>'}
      `;
    }).catch(() => {
      const tokenCard = document.getElementById("axis-right-token-card");
      if (tokenCard) {
        tokenCard.innerHTML =
          '<p class="axis-right-empty" style="margin:0">Usage data unavailable.</p>';
      }
    });
  }

  // ── LEFT panel renderers ──────────────────────────────────────────────────────

  function renderLeftStudy(courses) {
    if (!$leftInner) {
      return;
    }

    const newThreadHtml = `
        <div class="axis-left-section">
            <button class="axis-left-new-thread" id="axis-new-study-thread">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New Study Thread
            </button>
        </div>
    `;

    const coursesHtml =
      courses.length === 0
        ? '<p class="axis-left-empty">No AI-ready courses yet.</p>'
        : `<div class="axis-left-section-title">Your Courses</div>
           <ul class="axis-course-list" role="list">
               ${courses
                 .map(
                   (c) => `
                   <li>
                       <button class="axis-course-item ${c.has_ai ? "" : "axis-course-item--no-ai"}"
                               data-courseid="${c.id}" data-coursename="${esc(c.fullname)}"
                               ${c.has_ai ? "" : 'disabled title="No AI content ready for this course yet"'}>
                           <span class="axis-course-name">${esc(c.fullname)}</span>
                           <span class="axis-course-meta">
                               ${
                                 c.completed
                                   ? '<span class="axis-badge axis-badge--done">✓ Done</span>'
                                   : `<span class="axis-progress-mini">${c.progress}%</span>`
                               }
                               ${c.has_ai ? '<span class="axis-badge axis-badge--ai">AI</span>' : ""}
                           </span>
                       </button>
                   </li>
               `,
                 )
                 .join("")}
           </ul>`;

    $leftInner.innerHTML = newThreadHtml + coursesHtml;

    // Bind course click
    $leftInner
      .querySelectorAll(".axis-course-item[data-courseid]")
      .forEach((btn) => {
        btn.addEventListener("click", () => {
          const courseid = parseInt(btn.dataset.courseid, 10);
          const coursename = btn.dataset.coursename;
          onCourseSelected({ id: courseid, fullname: coursename });
          // Mark active
          $leftInner
            .querySelectorAll(".axis-course-item")
            .forEach((b) => b.classList.remove("axis-course-item--active"));
          btn.classList.add("axis-course-item--active");
        });
      });

    // Bind new thread — properly async so session is ended before UI reset
    const newBtn = el("axis-new-study-thread");
    if (newBtn) {
      newBtn.addEventListener("click", async () => {
        newBtn.disabled = true;
        await endCurrentSession();
        resetChat("Select a course to start studying.");
        newBtn.disabled = false;
      });
    }
  }

  function renderLeftSupport(threads) {
    if (!$leftInner) {
      return;
    }

    const threadsHtml =
      threads.length === 0
        ? '<p class="axis-left-empty">No previous threads.</p>'
        : threads
            .map(
              (t) => `
            <button class="axis-thread-item" data-threadid="${t.id}"
                    data-axissessionid="${esc(t.axis_session_id || '')}"
                    data-threadname="${esc(t.cm_name || "Support Chat")}"
                    data-threaddate="${esc(t.date_label)}"
                    data-threadmsgs="${t.message_count || 0}">
                <span class="axis-thread-icon">💬</span>
                <span class="axis-thread-body">
                    <span class="axis-thread-title">${esc(t.cm_name || "Support Chat")}</span>
                    <span class="axis-thread-meta">${esc(t.date_label)}</span>
                </span>
            </button>
          `,
            )
            .join("");

    $leftInner.innerHTML = `
        <div class="axis-left-section">
            <button class="axis-left-new-thread" id="axis-new-support-thread">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New Support Thread
            </button>
        </div>
        <div class="axis-left-section-title">Recent Threads</div>
        <div class="axis-thread-list">
            ${threadsHtml}
        </div>
    `;

    // Bind new thread — properly async so previous session is ended cleanly
    const newBtn = el("axis-new-support-thread");
    if (newBtn) {
      newBtn.addEventListener("click", async () => {
        newBtn.disabled = true;
        await endCurrentSession();
        state.sessionId = null;
        resetChat("");
        await startSupportSession();
        newBtn.disabled = false;
      });
    }

    // Bind thread item clicks — load message history
    $leftInner.querySelectorAll(".axis-thread-item[data-threadid]").forEach((btn) => {
      btn.addEventListener("click", async () => {
        $leftInner.querySelectorAll(".axis-thread-item").forEach(b => b.classList.remove("axis-thread-item--active"));
        btn.classList.add("axis-thread-item--active");

        const sessionId = btn.dataset.axissessionid || "";
        const name      = btn.dataset.threadname  || "Support Chat";
        const date      = btn.dataset.threaddate  || "";
        const msgs      = parseInt(btn.dataset.threadmsgs, 10) || 0;

        hide($placeholder);
        show($messages);
        hide($inputBar);
        $messages.innerHTML = "";

        // Show loading
        renderMessage("ai", "Loading conversation history…");

        let history = [];
        if (sessionId) {
          try {
            const result = await repo.getSessionMessages(sessionId);
            history = safeParseJson(result.messages, []);
          } catch (_) { /* fall through */ }
        }

        $messages.innerHTML = ""; // clear loading message

        if (history.length) {
          history.forEach(m => {
            renderMessage(m.role === "user" ? "user" : "ai", m.message);
          });
        } else {
          // No stored messages — show info card
          const msgWord = msgs === 1 ? "message" : "messages";
          renderMessage("ai-html", `
            <div class="axis-thread-info-card">
              <div class="axis-thread-info-header">
                <span class="axis-thread-info-icon">💬</span>
                <div>
                  <div class="axis-thread-info-title">${esc(name)}</div>
                  <div class="axis-thread-info-meta">${esc(date)}${msgs ? " · " + msgs + " " + msgWord : ""}</div>
                </div>
              </div>
              <p class="axis-thread-info-note">
                This session was started before message history was enabled. Start a new thread to continue getting help.
              </p>
            </div>
          `);
        }

        // Rename + continue chips
        if ($suggestionChips) {
          $suggestionChips.className = "axis-suggestion-chips";
          const renameId = "axis-rename-thread-" + (btn.dataset.threadid || "0");
          $suggestionChips.innerHTML = `
            <button class="axis-chip" id="axis-continue-support-thread">✚ New thread</button>
            <button class="axis-chip axis-chip--outline" id="${renameId}">✏️ Rename</button>
          `;
          show($suggestionChips);

          const continueBtn = document.getElementById("axis-continue-support-thread");
          if (continueBtn) {
            continueBtn.addEventListener("click", async () => {
              clearChips();
              await endCurrentSession();
              state.sessionId = null;
              $messages.innerHTML = "";
              show($inputBar);
              await startSupportSession();
            });
          }

          const renameBtn = document.getElementById(renameId);
          if (renameBtn && sessionId) {
            renameBtn.addEventListener("click", () => {
              // eslint-disable-next-line no-alert
              const newName = window.prompt("Rename this thread:", name);
              if (newName && newName.trim()) {
                repo.renameThread(sessionId, newName.trim()).then(() => {
                  btn.dataset.threadname = newName.trim();
                  btn.querySelector(".axis-thread-title").textContent = newName.trim();
                }).catch(() => {});
              }
            });
          }
        }
      });
    });
  }

  function renderLeftAnalysis(dashData) {
    if (!$leftInner) {
      return;
    }

    const events  = safeParseJson(dashData.upcoming_events_json, []);
    const threads = dashData._threads || [];   // filled separately if needed

    const eventsHtml = events.length === 0
      ? '<p class="axis-left-empty">No upcoming events in the next 30 days.</p>'
      : events.map(ev => `
          <div class="axis-event-item">
              <div class="axis-event-days-badge ${ev.days_away <= 3 ? "axis-event-days-badge--urgent" : ""}">
                  ${ev.days_away <= 0 ? "Today" : ev.days_away + "d"}
              </div>
              <div class="axis-event-body">
                  <div class="axis-event-name">${esc(ev.name)}</div>
                  <div class="axis-event-meta">${esc(ev.date_label)}${ev.coursename ? " · " + esc(ev.coursename) : ""}</div>
              </div>
          </div>`
      ).join("");

    const threadsHtml = threads.length === 0
      ? '<p class="axis-left-empty">No recent AI sessions.</p>'
      : threads.slice(0, 5).map(t => `
          <div class="axis-left-thread-item">
              <span class="axis-left-thread-mode axis-left-thread-mode--${esc(t.chat_mode)}">${esc(t.chat_mode)}</span>
              <span class="axis-left-thread-name">${esc(t.cm_name || "Thread")}</span>
              <span class="axis-left-thread-date">${esc(t.date_label)}</span>
          </div>`
      ).join("");

    $leftInner.innerHTML = `
        <div class="axis-left-section-title">📅 Upcoming Events</div>
        <div class="axis-events-list">${eventsHtml}</div>
        <div class="axis-left-section-title" style="margin-top:16px">💬 Recent AI Sessions</div>
        <div class="axis-recent-threads">${threadsHtml}</div>
    `;
  }

  // ── Mode transitions ──────────────────────────────────────────────────────────

  async function switchToWelcome() {
    await endCurrentSession();
    state.mode = null;

    hide($threepanel);
    show($welcomeView);
    hide($btnBack);
    hide($btnLeftToggle);
    hide($btnRightToggle);
    setHeaderMode(null);
    setHeaderText("Axis AI", "Your learning assistant");
    $panel.classList.remove("axis-panel--expanded");
  }

  async function switchToMode(mode) {
    state.mode = mode;

    hide($welcomeView);
    show($threepanel);
    show($btnBack);
    show($btnLeftToggle);
    show($btnRightToggle);
    $panel.classList.add("axis-panel--expanded");

    setHeaderMode(mode);

    // On mobile: start with both panels collapsed; on desktop: ensure both open
    if (isMobile()) {
      collapseLeft();
      collapseRight();
    } else {
      // Always expand both panels when entering a mode on desktop
      if (state.leftCollapsed) {
        expandLeft();
      }
      if (state.rightCollapsed) {
        expandRight();
      }
    }

    // Reset middle area
    hide($analysisArea);
    hide($inputBar);
    show($chatArea);
    hide($subtabs);

    // Render right panel content
    renderRightPanel(mode);

    switch (mode) {
      case "study":
        setHeaderText("Study with AI", "Chat about your courses");
        show($subtabs);
        resetChat("Select a course on the left to start studying.");
        // Load courses
        $leftInner.innerHTML =
          '<div class="axis-left-loading">' +
          '<div class="axis-skeleton-line"></div>' +
          '<div class="axis-skeleton-line"></div>' +
          '<div class="axis-skeleton-line"></div></div>';
        try {
          const courses = await repo.getEnrolledCourses();
          state.courses = courses;
          renderLeftStudy(courses);
        } catch (e) {
          $leftInner.innerHTML =
            '<p class="axis-left-empty axis-left-empty--error">Could not load courses.</p>';
        }
        break;

      case "support":
        setHeaderText("Support", "Knowledge base chat");
        // Load thread history in parallel while session is set up
        $leftInner.innerHTML =
          '<div class="axis-left-loading"><div class="axis-skeleton-line"></div><div class="axis-skeleton-line"></div></div>';
        try {
          const threads = await repo.getChatThreads("support", 10);
          renderLeftSupport(threads);
        } catch (e) {
          renderLeftSupport([]);
        }
        // Await session creation — state.sessionId is set before user can type
        await startSupportSession();
        // Show quick-start chips AFTER session ready (chips are now outside placeholder)
        renderSuggestionChips([
          "How do I reset my password?",
          "How do I enrol in a course?",
          "Where can I find my certificates?",
        ]);
        break;

      case "analysis":
        setHeaderText("My Analysis", "Your learning overview");
        hide($chatArea);
        show($analysisArea);
        hide($inputBar);
        renderRightAnalysis(null); // placeholder — refreshed inside loadAnalysis with real data
        // Load left panel data
        $leftInner.innerHTML =
          '<div class="axis-left-loading">' +
          '<div class="axis-skeleton-line"></div>' +
          '<div class="axis-skeleton-line"></div>' +
          '<div class="axis-skeleton-line"></div></div>';
        loadAnalysis();
        break;
    }
  }

  // ── Study: course → CM selection ─────────────────────────────────────────────

  async function onCourseSelected(course) {
    state.currentCourse = course;
    await endCurrentSession();
    renderRightPanel("study", course);

    // Show CM picker in middle
    $messages.innerHTML = "";
    hide($messages);
    $placeholderText.textContent = `Loading AI content for "${course.fullname}"…`;
    show($placeholder);
    hide($inputBar);
    hide($suggestionChips);

    try {
      const cms = await repo.getCourseAiCms(course.id);
      if (!cms.length) {
        $placeholderText.textContent =
          "No AI-ready activities found for this course yet.";
        return;
      }
      if (cms.length === 1) {
        // Only one CM — go straight to chat
        startStudySession(cms[0]);
      } else {
        renderCmPicker(cms);
      }
    } catch (e) {
      $placeholderText.textContent =
        "Could not load AI activities. Please try again.";
    }
  }

  function renderCmPicker(cms) {
    // Hide the decorative icon — cards replace it visually
    if ($placeholderIcon) {
      hide($placeholderIcon);
    }
    $placeholderText.textContent = "Choose an activity to study";
    $placeholderText.style.fontSize = "14px";
    $placeholderText.style.fontWeight = "600";
    $placeholderText.style.color = "var(--axis-dark)";

    if ($cmPickerArea) {
      $cmPickerArea.innerHTML = cms.map(cm =>
        `<button class="axis-cm-card" data-cmid="${cm.cmid}" data-name="${esc(cm.name)}" data-content="${esc(cm.content_item_id)}">
          <span class="axis-cm-card-icon">📖</span>
          <span class="axis-cm-card-name">${esc(cm.name)}</span>
        </button>`
      ).join("");
      show($cmPickerArea);

      $cmPickerArea.querySelectorAll(".axis-cm-card").forEach((btn) => {
        btn.addEventListener("click", () => {
          startStudySession({
            cmid: parseInt(btn.dataset.cmid, 10),
            name: btn.dataset.name,
            content_item_id: btn.dataset.content,
          });
        });
      });
    }
    show($placeholder);
  }

  async function startStudySession(cm) {
    state.currentCmid = cm.cmid;
    clearChips();
    hide($placeholder);
    show($messages);
    show($inputBar);
    $messages.innerHTML = "";

    // Show connecting state before session is ready
    renderMessage(
      "ai",
      `Hi ${state.initData?.firstname || "there"}! 👋 I'm ready to help you study **${cm.name}**.\n\nConnecting to your AI tutor…`,
    );
    $chatInput.focus();

    try {
      // Fetch session and CM outputs in parallel — outputs power local-intent shortcuts
      const [session, outputs] = await Promise.all([
        repo.createSession(cm.cmid, state.subtab === "practice" ? "practice" : "study"),
        repo.getCmOutputs(cm.cmid).catch(() => null),
      ]);
      state.sessionId = session.session_id;
      state.cmOutputs = outputs;

      // Session ready — update greeting and show chips
      $messages.innerHTML = "";
      renderMessage(
        "ai",
        `Hi ${state.initData?.firstname || "there"}! 👋 I'm ready to help you study **${cm.name}**.\n\nYou can ask me to explain concepts, quiz you, or summarise sections. What would you like to explore?`,
      );
      showStudyContextChips(); // feature-aware starters (only chips whose content exists)
    } catch (e) {
      $messages.innerHTML = "";
      state.cmOutputs = null;
      renderMessage(
        "ai",
        "Sorry, I couldn't start a session. Please check that AI is configured for this activity and try again.",
      );
    }
  }

  // ── Support: start KB session ─────────────────────────────────────────────────

  // Reopen a past study thread from the activity stream — loads stored messages and
  // the CM outputs so the learner can continue where they left off.
  async function openStudyThread(sessionId, cmid, name) {
    state.currentCmid = cmid;
    clearChips();
    hide($placeholder);
    show($messages);
    show($inputBar);
    $messages.innerHTML = "";
    renderMessage("ai", `Reopening **${name}**…`);
    try {
      // Reuse the stored messages for on-screen context, but start a FRESH backend
      // session — the original axisstand session is ephemeral and may no longer
      // accept messages (continuing a stale one returns a backend error reply).
      const [msgRes, session, outputs] = await Promise.all([
        repo.getSessionMessages(sessionId),
        repo.createSession(cmid, "study"),
        repo.getCmOutputs(cmid).catch(() => null),
      ]);
      state.sessionId = session.session_id;
      state.cmOutputs = outputs;
      const history = safeParseJson(msgRes.messages, []);
      $messages.innerHTML = "";
      renderMessage("ai", `Hi ${state.initData?.firstname || "there"}! 👋 We're continuing **${name}**. Ask a follow-up or use the buttons below.`);
      history.forEach(mm => renderMessage(mm.role === "user" ? "user" : "ai", mm.message));
      showStudyContextChips(); // feature-aware starters (only chips whose content exists)
      scrollMessages();
      $chatInput.focus();
    } catch (e) {
      window.console && window.console.error("[edzaiaxisfront] reopen thread failed:", e);
      $messages.innerHTML = "";
      state.cmOutputs = null;
      renderMessage("ai", "Sorry, I couldn't reopen this session. Please start a new one from the course list.");
    }
  }

  async function startSupportSession() {
    state.currentCmid = null;
    hide($placeholder);
    show($messages);
    show($inputBar);
    $messages.innerHTML = "";

    try {
      const session = await repo.createSession(0, "support");
      state.sessionId = session.session_id;
      renderMessage(
        "ai",
        "\uD83D\uDC4B Hi! I'm your support assistant. " +
          "I can search the knowledge base to answer your questions about the platform." +
          "\n\nWhat do you need help with?",
      );

      // Refresh the left panel so this new thread appears immediately
      try {
        const threads = await repo.getChatThreads("support", 10);
        renderLeftSupport(threads);
      } catch (_) { /* non-critical — panel will refresh on next open */ }

    } catch (e) {
      console.warn("Could not create support session:", e);
      renderMessage(
        "ai",
        "Support chat is currently unavailable. Please try again later or contact your administrator.",
      );
    }
    $chatInput.focus();
  }

  // ── Analysis: load data & render full enterprise dashboard ───────────────────

  async function loadAnalysis() {
    show($analysisSkeleton);
    hide($analysisContent);

    try {
      // Single call for everything
      const d = await repo.getAnalysisDashboard();

      // ── parse JSON fields ──────────────────────────────────────────────────
      const courses  = safeParseJson(d.courses_json, []);
      const due      = safeParseJson(d.due_soon_json, []);
      const chat     = safeParseJson(d.chat_stats_json, {});
      const recs     = safeParseJson(d.recommendations_json, []);
      const badges   = safeParseJson(d.badges_json, []);
      const certs    = safeParseJson(d.certs_json, []);

      // ── left panel (events + recent threads) ──────────────────────────────
      renderLeftAnalysis(d);

      // ── right panel (streak + tips) ───────────────────────────────────────
      renderRightAnalysis(d);

      // ── build hero stats ──────────────────────────────────────────────────
      const gradeHtml = d.avg_grade >= 0
        ? `<div class="axis-dash-stat">
             <div class="axis-dash-stat-icon">📊</div>
             <div class="axis-dash-stat-val">${d.avg_grade}%</div>
             <div class="axis-dash-stat-lbl">Avg Grade</div>
           </div>`
        : "";

      const heroHtml = `
        <div class="axis-dash-hero">
          <div class="axis-dash-stat axis-dash-stat--blue">
            <div class="axis-dash-stat-icon">📚</div>
            <div class="axis-dash-stat-val">${d.courses_total}</div>
            <div class="axis-dash-stat-lbl">Enrolled</div>
          </div>
          <div class="axis-dash-stat axis-dash-stat--green">
            <div class="axis-dash-stat-icon">✅</div>
            <div class="axis-dash-stat-val">${d.courses_completed}</div>
            <div class="axis-dash-stat-lbl">Completed</div>
          </div>
          <div class="axis-dash-stat axis-dash-stat--orange">
            <div class="axis-dash-stat-icon">🔥</div>
            <div class="axis-dash-stat-val">${d.streak_days || 0}</div>
            <div class="axis-dash-stat-lbl">Day Streak</div>
          </div>
          <div class="axis-dash-stat axis-dash-stat--purple">
            <div class="axis-dash-stat-icon">🏅</div>
            <div class="axis-dash-stat-val">${d.badge_count + d.cert_count}</div>
            <div class="axis-dash-stat-lbl">Awards</div>
          </div>
          <div class="axis-dash-stat axis-dash-stat--indigo">
            <div class="axis-dash-stat-icon">📈</div>
            <div class="axis-dash-stat-val">${d.progress_pct}%</div>
            <div class="axis-dash-stat-lbl">Progress</div>
          </div>
          ${gradeHtml}
        </div>`;

      // ── due soon ──────────────────────────────────────────────────────────
      const dueHtml = due.length === 0
        ? `<div class="axis-dash-empty-note">✅ Nothing due in the next 14 days — you're all caught up!</div>`
        : due.map(item => {
            const cls = item.days_left === 0 ? "urgent" : item.days_left <= 3 ? "high" : "medium";
            const badge = item.days_left === 0 ? "Today" : item.days_left === 1 ? "Tomorrow" : `${item.days_left}d`;
            const icon  = item.type === "assignment" ? "📝" : "🎯";
            return `<div class="axis-dash-due-item axis-dash-due--${cls}">
              <div class="axis-dash-due-badge">${badge}</div>
              <div class="axis-dash-due-body">
                <div class="axis-dash-due-name">${icon} ${esc(item.name)}</div>
                <div class="axis-dash-due-meta">${esc(item.type.charAt(0).toUpperCase() + item.type.slice(1))} · ${esc(item.coursename)}</div>
              </div>
            </div>`;
          }).join("");

      // ── course progress grid ──────────────────────────────────────────────
      const coursesHtml = courses.slice(0, 8).map(c => {
        const pct   = c.progress || 0;
        const grade = c.grade !== null ? `<span class="axis-dash-course-grade">${c.grade}%</span>` : "";
        const tick  = c.completed ? `<span class="axis-dash-course-done">✓</span>` : "";
        return `<div class="axis-dash-course-row">
          <div class="axis-dash-course-name">${tick}${esc(c.fullname)}</div>
          <div class="axis-dash-course-bar-wrap">
            <div class="axis-dash-course-bar">
              <div class="axis-dash-course-bar-fill ${pct === 100 ? "axis-dash-course-bar-fill--done" : ""}"
                   style="width:${pct}%"></div>
            </div>
            <span class="axis-dash-course-pct">${pct}%</span>
            ${grade}
          </div>
        </div>`;
      }).join("") || `<div class="axis-dash-empty-note">No courses enrolled yet.</div>`;

      // ── chat intelligence ─────────────────────────────────────────────────
      const weekly   = (chat.weekly_msgs || []).slice(-8);
      const maxCount = Math.max(...weekly.map(w => w.count), 1);
      const barsHtml = weekly.map(w => `
        <div class="axis-bar-item">
          <div class="axis-bar" style="--bar-h:${Math.round((w.count / maxCount) * 100)}%" title="${w.count} messages"></div>
          <div class="axis-bar-label">${esc(w.label)}</div>
        </div>`).join("");

      const topCmsHtml = (chat.top_cms || []).length === 0
        ? `<span class="axis-dash-empty-note">No AI sessions yet.</span>`
        : (chat.top_cms || []).map(t =>
            `<span class="axis-dash-topic-tag">${esc(t.cmname)} <span class="axis-dash-topic-count">${t.count}</span></span>`
          ).join("");

      const chatHtml = `
        <div class="axis-dash-chat-stats">
          <div class="axis-dash-mini-stat">
            <span class="axis-dash-mini-val">${chat.total_sessions || 0}</span>
            <span class="axis-dash-mini-lbl">Sessions</span>
          </div>
          <div class="axis-dash-mini-stat">
            <span class="axis-dash-mini-val">${chat.total_messages || 0}</span>
            <span class="axis-dash-mini-lbl">Messages</span>
          </div>
          <div class="axis-dash-mini-stat">
            <span class="axis-dash-mini-val">${chat.active_days || 0}</span>
            <span class="axis-dash-mini-lbl">Active Days</span>
          </div>
          <div class="axis-dash-mini-stat">
            <span class="axis-dash-mini-val">${chat.study_sessions || 0}</span>
            <span class="axis-dash-mini-lbl">Study</span>
          </div>
        </div>
        ${barsHtml ? `<div class="axis-bar-chart">${barsHtml}</div>` : ""}
        ${(chat.top_cms || []).length > 0
          ? `<div class="axis-dash-top-topics">
               <div class="axis-dash-section-subtitle">Most studied topics</div>
               <div class="axis-dash-topic-tags">${topCmsHtml}</div>
             </div>`
          : ""}`;

      // ── recommendations ───────────────────────────────────────────────────
      const recsHtml = recs.length === 0
        ? `<div class="axis-dash-empty-note">🎉 No action needed — you're on track!</div>`
        : recs.map(r => `
            <div class="axis-dash-rec axis-dash-rec--${r.priority}">
              <div class="axis-dash-rec-icon">${r.icon}</div>
              <div class="axis-dash-rec-body">
                <div class="axis-dash-rec-title">${esc(r.title)}</div>
                <div class="axis-dash-rec-desc">${esc(r.description)}</div>
              </div>
              ${r.cmid > 0
                ? `<a class="axis-dash-rec-action" href="/mod/page/view.php?id=${r.cmid}" target="_blank">→</a>`
                : `<span class="axis-dash-rec-action-arrow">→</span>`}
            </div>`
          ).join("");

      // ── render into the analysis content area ────────────────────────────
      $analysisContent.innerHTML = `
        ${heroHtml}
        <div class="axis-dash-section">
          <div class="axis-dash-section-title">⏰ Due Soon</div>
          <div class="axis-dash-due-list">${dueHtml}</div>
        </div>
        <div class="axis-dash-section">
          <div class="axis-dash-section-title">📖 My Courses</div>
          <div class="axis-dash-courses">${coursesHtml}</div>
        </div>
        <div class="axis-dash-section">
          <div class="axis-dash-section-title">💬 AI Learning Activity</div>
          ${chatHtml}
        </div>
        <div class="axis-dash-section">
          <div class="axis-dash-section-title">🎯 Adaptive Recommendations</div>
          <div class="axis-dash-recs">${recsHtml}</div>
        </div>
      `;

      hide($analysisSkeleton);
      show($analysisContent);

    } catch (e) {
      console.warn("Analysis dashboard error:", e);
      hide($analysisSkeleton);
      show($analysisContent);
      $analysisContent.innerHTML = `
        <div class="axis-dash-error">
          <p>⚠️ Could not load your dashboard right now. Please try again in a moment.</p>
        </div>`;
    }
  }

  // ── Local-intent shortcut ─────────────────────────────────────────────────────
  // For chips like "Give me a summary" or "Create some flashcards", serve
  // directly from the pre-loaded Moodle CM outputs rather than making an LLM
  // call.  This is instant, uses full extracted text, and avoids the incomplete-
  // chunk problem.  Returns true if the intent was handled locally.

  function safeParseJson(str, fallback) {
    try {
      return JSON.parse(str || "[]");
    } catch {
      return fallback;
    }
  }

  // Build an interactive flip-card deck for the chatbot message area — mirrors the
  // in-course AI Assistant deck (click to flip, prev/next). Multiple decks can coexist
  // in the chat log; per-deck state lives in data-cards / data-idx and is driven by a
  // delegated click handler (see setup()).
  function buildFlashcardHtml(batch, startIdx) {
    const esc = (s) => String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;");
    const escAttr = (s) => esc(s).replace(/"/g,"&quot;").replace(/'/g,"&#39;");
    const cards = batch.map((c) => ({
      front: String(c.front || c.question || ""),
      back:  String(c.back  || c.answer   || ""),
    }));
    const first = cards[0] || { front: "", back: "" };
    return `<div class="edzai-flashcard-deck axis-fc-deck" data-idx="0" data-cards="${escAttr(JSON.stringify(cards))}">
      <div class="edzai-flashcard" tabindex="0" title="Click to flip" role="button" aria-label="Flashcard — click to flip">
        <div class="edzai-fc-inner">
          <div class="edzai-fc-face edzai-fc-front">
            <div class="edzai-fc-label">Question</div>
            <p class="edzai-fc-text">${esc(first.front)}</p>
            <span class="edzai-fc-hint">Tap to flip</span>
          </div>
          <div class="edzai-fc-face edzai-fc-back">
            <div class="edzai-fc-label">Answer</div>
            <p class="edzai-fc-text">${esc(first.back)}</p>
            <span class="edzai-fc-hint">Tap to flip back</span>
          </div>
        </div>
      </div>
      <div class="edzai-fc-controls">
        <button type="button" class="edzai-fc-nav axis-fc-prev" aria-label="Previous card">&#8249;</button>
        <span class="edzai-fc-counter">1 / ${cards.length}</span>
        <button type="button" class="edzai-fc-nav axis-fc-next" aria-label="Next card">&#8250;</button>
      </div>
    </div>`;
  }

  // Build a clean key-terms card list (replaces the old plain "Term: definition" text).
  // Essential layout is inlined so it renders correctly even before the aggregated
  // theme CSS bundle refreshes; the .axis-kt-* classes let the stylesheet add hover polish.
  function buildKeyTermsHtml(batch) {
    const esc = (s) => String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;");
    return `<div class="axis-kt-list" style="display:flex;flex-direction:column;gap:8px;margin:8px 0 4px;">${batch.map((t) =>
      `<div class="axis-kt-item" style="background:#fff;border:1px solid rgba(30,42,56,0.1);border-left:3px solid #c97216;border-radius:10px;padding:10px 13px;">`
      + `<div class="axis-kt-term" style="font-size:13.5px;font-weight:700;color:#c97216;margin-bottom:2px;">${esc(t.term || "")}</div>`
      + `<div class="axis-kt-def" style="font-size:13px;line-height:1.5;color:#1e2a38;">${esc(t.definition || "")}</div>`
      + `</div>`
    ).join("")}</div>`;
  }

  // Build an interactive quiz (clickable options + instant feedback). Reuses the
  // in-course AI Assistant's edzai-quiz styles; graded client-side against q.answer.
  function buildQuizHtml(questions) {
    const esc = (s) => String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;");
    const escAttr = (s) => esc(s).replace(/"/g,"&quot;").replace(/'/g,"&#39;");
    const uid = "aq" + Date.now();
    return `<div class="edzai-quiz axis-quiz">${questions.map((q, i) => {
      const opts = safeParseJson(q.options_json, []);
      const ans = q.answer || q.correct_answer || "";
      const optHtml = opts.length
        ? `<div class="edzai-quiz-options">${opts.map((o, j) =>
            `<label class="edzai-quiz-option"><input type="radio" name="${uid}-${i}" value="${j}"><span>${esc(o)}</span></label>`
          ).join("")}</div>`
        : `<input type="text" class="edzai-quiz-freetext" placeholder="Your answer…">`;
      return `<div class="edzai-quiz-question" data-answer="${escAttr(ans)}">
        <p class="edzai-quiz-qtext">${i + 1}. ${esc(q.question_text || "")}</p>
        ${optHtml}
        <div class="edzai-quiz-feedback" style="display:none"></div>
        <button type="button" class="edzai-quiz-check axis-quiz-check">Check answer</button>
      </div>`;
    }).join("")}</div>`;
  }

  function tryLocalIntent(text) {
    const outputs = state.cmOutputs;
    if (!outputs) {
      return false;
    }
    const m = text.toLowerCase();
    const esc = (s) => String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;");

    // ── "Show more" pagination (check BEFORE fresh-intent matchers) ──────────

    if (/\bshow\s+more\s+flash/.test(m)) {
      const cards = safeParseJson(outputs.flashcards, []);
      if (!cards.length || state.flashcardOffset >= cards.length) {
        renderMessage("ai", "No more flashcards — that's all of them!");
        return true;
      }
      const batch = cards.slice(state.flashcardOffset, state.flashcardOffset + 5);
      const html  = buildFlashcardHtml(batch, state.flashcardOffset);
      state.flashcardOffset += batch.length;
      const remaining = cards.length - state.flashcardOffset;
      const footer = remaining > 0
        ? `<p class="axis-chat-cards-more"><em>${remaining} more — ask me "show more flashcards".</em></p>`
        : `<p class="axis-chat-cards-more"><em>That's all ${cards.length} flashcards!</em></p>`;
      renderMessage("ai-html", `<p>Here are more flashcards:</p>${html}${footer}`);
      return true;
    }

    if (/\bshow\s+more\s+(key\s*term|glossary|term)/.test(m)) {
      const terms = safeParseJson(outputs.glossary, []);
      if (!terms.length || state.glossaryOffset >= terms.length) {
        renderMessage("ai", "No more terms — that's all of them!");
        return true;
      }
      const batch = terms.slice(state.glossaryOffset, state.glossaryOffset + 6);
      const body  = buildKeyTermsHtml(batch);
      state.glossaryOffset += batch.length;
      const remaining = terms.length - state.glossaryOffset;
      const footer = remaining > 0
        ? `<p class="axis-chat-cards-more"><em>${remaining} more — ask me "show more key terms".</em></p>`
        : `<p class="axis-chat-cards-more"><em>That's all ${terms.length} key terms!</em></p>`;
      renderMessage("ai-html", `<p>More key terms:</p>${body}${footer}`);
      return true;
    }

    // ── Fresh intents (reset pagination offsets) ──────────────────────────────

    // Summary
    if (/\bsummar(y|iz|is)/.test(m) && outputs.summary) {
      renderMessage("ai", `Here's a summary of this content:\n\n${outputs.summary}`);
      showStudyContextChips();
      return true;
    }

    // Flashcards (initial)
    if (/\bflashcard/.test(m)) {
      const cards = safeParseJson(outputs.flashcards, []);
      if (cards.length) {
        const batch = cards.slice(0, 5);
        state.flashcardOffset = 5;
        const html = buildFlashcardHtml(batch, 0);
        const remaining = cards.length - 5;
        const footer = remaining > 0
          ? `<p class="axis-chat-cards-more"><em>${remaining} more cards — ask me "show more flashcards".</em></p>`
          : "";
        renderMessage("ai-html", `<p>Here are flashcards for this content:</p>${html}${footer}`);
        showStudyContextChips();
        return true;
      }
    }

    // Glossary / key terms / key concepts (initial)
    if (/\b(glossary|key.?term|key.?concept|terminolog)/.test(m)) {
      const terms = safeParseJson(outputs.glossary, []);
      if (terms.length) {
        const batch = terms.slice(0, 6);
        state.glossaryOffset = 6;
        const body = buildKeyTermsHtml(batch);
        const remaining = terms.length - 6;
        const footer = remaining > 0
          ? `<p class="axis-chat-cards-more"><em>${remaining} more terms — ask me "show more key terms".</em></p>`
          : "";
        renderMessage("ai-html", `<p>Key terms for this content:</p>${body}${footer}`);
        showStudyContextChips();
        return true;
      }
    }

    // Quiz / test me
    if (/\b(quiz|test me|practice question)/.test(m)) {
      const questions = safeParseJson(outputs.quiz, []);
      if (questions.length) {
        const show = questions.slice(0, 3);
        renderMessage("ai-html",
          `<p>Here are some practice questions — pick an answer and hit <strong>Check</strong>:</p>${buildQuizHtml(show)}`
        );
        showStudyContextChips();
        return true;
      }
    }

    // FAQ
    if (/\b(faq|frequent|common.?question)/.test(m)) {
      const faqs = safeParseJson(outputs.faq, []);
      if (faqs.length) {
        const show = faqs.slice(0, 4);
        const body = show.map((f, i) =>
          `**Q${i + 1}:** ${f.question || f.q || ""}\n**A:** ${f.answer || f.a || ""}`
        ).join("\n\n");
        renderMessage("ai", `Frequently asked questions:\n\n${body}`);
        showStudyContextChips();
        return true;
      }
    }

    // "Show me visually" / visual overview / diagram / infographic
    if (/\b(visual|diagram|infographic|overview|chart|at.a.glance|visually)\b/.test(m)) {
      const cards     = safeParseJson(outputs.flashcards, []);
      const terms     = safeParseJson(outputs.glossary, []);
      const questions = safeParseJson(outputs.quiz, []);

      // Content stat chips
      const stats = [
        cards.length     ? `<div class="edzai-vis-stat"><span class="edzai-vis-stat-val">${cards.length}</span><span class="edzai-vis-stat-lbl">🃏 Flashcards</span></div>` : "",
        questions.length ? `<div class="edzai-vis-stat"><span class="edzai-vis-stat-val">${questions.length}</span><span class="edzai-vis-stat-lbl">🎯 Quiz Qs</span></div>` : "",
        terms.length     ? `<div class="edzai-vis-stat"><span class="edzai-vis-stat-val">${terms.length}</span><span class="edzai-vis-stat-lbl">📖 Key Terms</span></div>` : "",
      ].filter(Boolean).join("");

      // Parse objectives from HTML (line by line)
      const objHtml = outputs.objectives
        ? (() => {
            const raw = outputs.objectives
              .replace(/<li[^>]*>/gi, "\n")
              .replace(/<[^>]+>/g, "")
              .split(/\n/)
              .map(s => s.trim())
              .filter(s => s.length > 4 && s.length < 300);
            if (!raw.length) {
              return "";
            }
            return `<div class="edzai-vis-section-title">🎓 Learning Objectives</div>
              <ol class="edzai-vis-objectives">
                ${raw.slice(0, 6).map(o =>
                  `<li class="edzai-vis-obj-item"><span class="edzai-vis-obj-num"></span>${esc(o)}</li>`
                ).join("")}
              </ol>`;
          })()
        : "";

      // Key topic chips from glossary
      const termChips = terms.slice(0, 10).map(t =>
        `<span class="edzai-vis-term-chip">${esc(t.term)}</span>`
      ).join("");
      const termHtml = termChips
        ? `<div class="edzai-vis-section-title">🏷️ Key Topics</div>
           <div class="edzai-vis-term-chips">${termChips}</div>`
        : "";

      // Quiz difficulty breakdown bar
      const diffCount = { easy: 0, medium: 0, hard: 0 };
      questions.forEach(q => {
        if (diffCount[q.difficulty] !== undefined) {
          diffCount[q.difficulty]++;
        }
      });
      const diffTotal = diffCount.easy + diffCount.medium + diffCount.hard;
      const diffHtml = diffTotal > 0
        ? `<div class="edzai-vis-section-title">📊 Quiz Difficulty</div>
           <div class="edzai-vis-diff-bar">
             ${diffCount.easy   ? `<div class="edzai-vis-diff-seg edzai-vis-diff--easy"   style="flex:${diffCount.easy}"   title="Easy: ${diffCount.easy}">Easy ${diffCount.easy}</div>` : ""}
             ${diffCount.medium ? `<div class="edzai-vis-diff-seg edzai-vis-diff--medium" style="flex:${diffCount.medium}" title="Medium: ${diffCount.medium}">Med ${diffCount.medium}</div>` : ""}
             ${diffCount.hard   ? `<div class="edzai-vis-diff-seg edzai-vis-diff--hard"   style="flex:${diffCount.hard}"   title="Hard: ${diffCount.hard}">Hard ${diffCount.hard}</div>` : ""}
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
          <p class="edzai-vis-footer"><em>Ask for flashcards, key terms, or quiz questions for the full interactive experience.</em></p>
        </div>`;

      renderMessage("ai-html", `<p>Here's a visual overview of this content:</p>${visHtml}`);
      showStudyContextChips();
      return true;
    }

    return false; // not a local intent — fall through to RAG chat
  }

  // ── Chat: send message ────────────────────────────────────────────────────────

  async function sendMessage(text) {
    if (!text.trim() || state.waiting) {
      return;
    }

    text = text.trim();
    clearChips();
    renderMessage("user", text);
    $chatInput.value = "";
    $chatInput.style.height = "auto";

    // Serve from Moodle's pre-loaded CM outputs if the intent matches —
    // no tokens used, no async wait, uses the full generated content.
    if (state.sessionId && tryLocalIntent(text)) {
      $sendBtn.disabled = !$chatInput.value.trim();
      scrollMessages();
      $chatInput.focus();
      return;
    }
    $chatInput.disabled = true;  // lock textarea while waiting
    $sendBtn.disabled = true;
    state.waiting = true;
    show($typing);
    scrollMessages();

    if (!state.sessionId) {
      hide($typing);
      renderMessage(
        "ai",
        state.mode === "support"
          ? "Still connecting to support… please wait a moment and try again."
          : "Please select a course and activity first.",
      );
      state.waiting = false;
      $chatInput.disabled = false;
      $sendBtn.disabled = false;
      return;
    }

    try {
      const result = await repo.sendMessage(state.sessionId, text);
      hide($typing);
      renderMessage("ai", result.reply || "No response received.");

      // Suggestions come back as a JSON string — parse then map to chip labels
      const suggestions = typeof result.suggestions === "string"
        ? JSON.parse(result.suggestions || "[]")
        : (result.suggestions || []);
      if (suggestions.length) {
        renderSuggestionChips(
          suggestions.map((s) => s.label || s.payload || "")
            .filter(Boolean)
            .filter((label) => !/\[[^\]]*\]/.test(label))
        );
      }
    } catch (e) {
      hide($typing);
      window.console && window.console.error("[edzaiaxisfront] chat send failed:", e);
      renderMessage("ai", "Sorry, something went wrong. Please try again in a moment.");
    } finally {
      state.waiting = false;
      $chatInput.disabled = false;  // unlock textarea
      $sendBtn.disabled = !$chatInput.value.trim();
      $chatInput.focus();
      scrollMessages();
    }
  }

  // ── Session lifecycle ─────────────────────────────────────────────────────────

  async function endCurrentSession() {
    if (state.sessionId) {
      try {
        await repo.endSession(state.sessionId);
      } catch (e) {
        /* silent */
      }
      state.sessionId = null;
    }
  }

  function resetChat(placeholder = "") {
    $messages.innerHTML = "";
    hide($messages);
    $placeholderText.textContent = placeholder;
    $placeholderText.style.fontSize = "";
    $placeholderText.style.fontWeight = "";
    $placeholderText.style.color = "";
    if ($placeholderIcon) {
      show($placeholderIcon);
    }
    if ($cmPickerArea) {
      $cmPickerArea.innerHTML = "";
      hide($cmPickerArea);
    }
    show($placeholder);
    hide($inputBar);
    clearChips();
    hide($typing);
    state.waiting = false;
    state.currentCmid = null;
    state.cmOutputs = null;
    state.flashcardOffset = 0;
    state.glossaryOffset  = 0;
  }

  // ── Panel toggle helpers ──────────────────────────────────────────────────────

  const isMobile = () => window.innerWidth < 768;

  function collapseRight() {
    state.rightCollapsed = true;
    $rightPanel.classList.add("axis-right--collapsed");
    if (isMobile()) {
      $rightPanel.classList.remove("axis-right--mobile-open");
    }
    if ($btnRightToggle) {
      $btnRightToggle.classList.remove("axis-btn-right-toggle--active");
    }
  }

  function expandRight() {
    state.rightCollapsed = false;
    $rightPanel.classList.remove("axis-right--collapsed");
    if (isMobile()) {
      $rightPanel.classList.add("axis-right--mobile-open");
    }
    if ($btnRightToggle) {
      $btnRightToggle.classList.add("axis-btn-right-toggle--active");
    }
  }

  function toggleRight() {
    state.rightCollapsed ? expandRight() : collapseRight();
  }

  function collapseLeft() {
    state.leftCollapsed = true;
    if ($leftPanel) {
      $leftPanel.classList.add("axis-left--collapsed");
      if (isMobile()) {
        $leftPanel.classList.remove("axis-left--mobile-open");
      }
    }
    if ($btnLeftToggle) {
      $btnLeftToggle.classList.remove("axis-btn-left-toggle--active");
    }
  }

  function expandLeft() {
    state.leftCollapsed = false;
    if ($leftPanel) {
      $leftPanel.classList.remove("axis-left--collapsed");
      if (isMobile()) {
        $leftPanel.classList.add("axis-left--mobile-open");
      }
    }
    if ($btnLeftToggle) {
      $btnLeftToggle.classList.add("axis-btn-left-toggle--active");
    }
  }

  function toggleLeft() {
    state.leftCollapsed ? expandLeft() : collapseLeft();
  }

  // ── Subtab switch ─────────────────────────────────────────────────────────────

  function switchSubtab(tab) {
    state.subtab = tab;
    document
      .querySelectorAll(".axis-subtab")
      .forEach((t) => t.classList.remove("axis-subtab--active"));
    const activeTab = el("axis-subtab-" + tab);
    if (activeTab) {
      activeTab.classList.add("axis-subtab--active");
    }
  }

  // ── Panel open / close ────────────────────────────────────────────────────────

  async function openPanel() {
    state.open = true;
    $panel.hidden = false;
    $backdrop.hidden = false;
    $trigger.querySelector(".axis-trigger-icon--open").hidden = true;
    $trigger.querySelector(".axis-trigger-icon--close").hidden = false;
    $panel.classList.add("axis-panel--open");

    // Load init data if not yet loaded
    if (!state.initData) {
      await loadInitData();
    }
  }

  function closePanel() {
    state.open = false;
    $panel.classList.remove("axis-panel--open");
    $backdrop.hidden = true;
    $trigger.querySelector(".axis-trigger-icon--open").hidden = false;
    $trigger.querySelector(".axis-trigger-icon--close").hidden = true;
    // Wait for transition then hide
    window.setTimeout(() => {
      $panel.hidden = true;
    }, 300);
  }

  function minimisePanel() {
    state.minimised = !state.minimised;
    $panel.classList.toggle("axis-panel--minimised", state.minimised);
  }

  // ── Init data load ────────────────────────────────────────────────────────────

  async function loadInitData() {
    show($welcomeSkeleton);
    hide($welcomeContent);

    try {
      const data = await repo.getInitData();
      state.initData = data;

      // Populate stats
      if ($welcomeName) {
        $welcomeName.textContent = "Hi, " + data.firstname + "! \uD83D\uDC4B";
      }
      if ($statCoursesVal) {
        $statCoursesVal.textContent = data.course_count;
      }
      if ($statBadgesVal) {
        $statBadgesVal.textContent = data.badge_count;
      }
      if ($statStreakVal) {
        $statStreakVal.textContent = data.streak_days > 0
          ? data.streak_days + "d"
          : "\u2014";
      }
      if ($statPointsVal) {
        $statPointsVal.textContent = data.points;
      }
      if ($statPointsLbl) {
        $statPointsLbl.textContent = data.points_label;
      }

      // Disable modes that aren't available
      const studyBtn = el("axis-mode-study");
      const supportBtn = el("axis-mode-support");

      if (studyBtn && !data.study_enabled) {
        studyBtn.classList.add("axis-mode-btn--disabled");
        studyBtn.title =
          "Study with AI is not available — no AI-ready courses yet.";
      }
      if (supportBtn && !data.support_enabled) {
        supportBtn.classList.add("axis-mode-btn--disabled");
        supportBtn.title = "Support chat is not currently enabled.";
      }

      hide($welcomeSkeleton);
      show($welcomeContent);
    } catch (e) {
      hide($welcomeSkeleton);
      show($welcomeContent);
      if ($welcomeName) {
        $welcomeName.textContent =
          "Hi, " + (state.config.firstname || "there") + "! \uD83D\uDC4B";
      }
    }
  }

  // ── Bind events ───────────────────────────────────────────────────────────────

  function bindEvents() {
    // Trigger button
    $trigger.addEventListener("click", () => {
      if (state.open) {
        closePanel();
      } else {
        openPanel();
      }
    });

    // Backdrop
    $backdrop.addEventListener("click", closePanel);

    // Header buttons
    $btnClose.addEventListener("click", closePanel);
    $btnMinimise.addEventListener("click", minimisePanel);
    $btnBack.addEventListener("click", () => switchToWelcome());

    // Clicking the header while minimised restores the panel
    $header.addEventListener("click", (e) => {
      if (state.minimised && !e.target.closest(".axis-header-btn")) {
        minimisePanel(); // toggles back to expanded
      }
    });

    // Mode buttons
    ["study", "support", "analysis"].forEach((mode) => {
      const btn = el("axis-mode-" + mode);
      if (btn) {
        btn.addEventListener("click", () => {
          if (!btn.classList.contains("axis-mode-btn--disabled")) {
            switchToMode(mode);
          }
        });
      }
    });

    // Subtabs
    document.querySelectorAll(".axis-subtab").forEach((btn) => {
      btn.addEventListener("click", () => switchSubtab(btn.dataset.subtab));
    });

    // Send button
    $sendBtn.addEventListener("click", () => sendMessage($chatInput.value));

    // Input field
    $chatInput.addEventListener("input", () => {
      autoGrow($chatInput);
      $sendBtn.disabled = !$chatInput.value.trim() || state.waiting;
      // Char count
      const len = $chatInput.value.length;
      if (len > 1800) {
        $charCount.textContent = `${len}/2000`;
        show($charCount);
      } else {
        hide($charCount);
      }
    });

    $chatInput.addEventListener("keydown", (e) => {
      if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        sendMessage($chatInput.value);
      }
    });

    // Suggestion chip delegation (static and dynamic)
    document.addEventListener("click", (e) => {
      const chip = e.target.closest(".axis-chip:not(.axis-chip--cm)");
      if (chip && chip.dataset.msg) {
        $chatInput.value = chip.dataset.msg;
        sendMessage($chatInput.value);
      }
    });

    // Flashcard flip + prev/next navigation (interactive decks rendered in chat)
    document.addEventListener("click", (e) => {
      const deck = e.target.closest(".axis-fc-deck");
      if (!deck) { return; }
      const card = deck.querySelector(".edzai-flashcard");
      const isNext = e.target.closest(".axis-fc-next");
      const isPrev = e.target.closest(".axis-fc-prev");
      if (isNext || isPrev) {
        let cards;
        try { cards = JSON.parse(deck.dataset.cards || "[]"); } catch { cards = []; }
        if (!cards.length) { return; }
        let idx = parseInt(deck.dataset.idx || "0", 10);
        idx = isNext ? Math.min(cards.length - 1, idx + 1) : Math.max(0, idx - 1);
        deck.dataset.idx = String(idx);
        const front = deck.querySelector(".edzai-fc-front .edzai-fc-text");
        const back  = deck.querySelector(".edzai-fc-back .edzai-fc-text");
        const ctr   = deck.querySelector(".edzai-fc-counter");
        if (front) { front.textContent = cards[idx].front; }
        if (back)  { back.textContent  = cards[idx].back; }
        if (ctr)   { ctr.textContent   = (idx + 1) + " / " + cards.length; }
        if (card)  { card.classList.remove("flipped"); }
        return;
      }
      if (card && e.target.closest(".edzai-flashcard")) {
        card.classList.toggle("flipped");
      }
    });

    // Reopen a past study thread from the "Your Study Activity" list
    document.addEventListener("click", (e) => {
      const item = e.target.closest(".axis-right-activity-item[data-session]");
      if (!item) { return; }
      openStudyThread(item.dataset.session, parseInt(item.dataset.cmid, 10), item.dataset.name || "this activity");
    });

    // Interactive quiz — grade a question when "Check answer" is clicked
    document.addEventListener("click", (e) => {
      const btn = e.target.closest(".axis-quiz-check");
      if (!btn) { return; }
      const qDiv = btn.closest(".edzai-quiz-question");
      if (!qDiv) { return; }
      const answer = (qDiv.dataset.answer || "").trim();
      const selected = qDiv.querySelector(".edzai-quiz-option input:checked");
      const freetext = qDiv.querySelector(".edzai-quiz-freetext");
      const userAnswer = selected
        ? ((selected.closest(".edzai-quiz-option").querySelector("span").textContent) || "").trim()
        : (freetext ? freetext.value.trim() : "");
      if (!userAnswer) { return; }
      const feedback = qDiv.querySelector(".edzai-quiz-feedback");
      const isCorrect = userAnswer.toLowerCase() === answer.toLowerCase();
      feedback.className = "edzai-quiz-feedback " + (isCorrect ? "correct" : "incorrect");
      feedback.textContent = isCorrect ? "✓ Correct!" : ("✗ Correct answer: " + answer);
      feedback.style.display = "block";
      qDiv.querySelectorAll(".edzai-quiz-option").forEach((opt) => {
        const span = opt.querySelector("span");
        const val = ((span && span.textContent) || "").trim().toLowerCase();
        const input = opt.querySelector("input");
        if (val === answer.toLowerCase()) { opt.classList.add("correct"); }
        else if (input && input.checked) { opt.classList.add("incorrect"); }
      });
    });

    // Left / right panel toggle buttons in header
    if ($btnLeftToggle) {
      $btnLeftToggle.addEventListener("click", toggleLeft);
    }
    if ($btnRightToggle) {
      $btnRightToggle.addEventListener("click", toggleRight);
    }

    // Keyboard trap in panel
    $panel.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        closePanel();
      }
    });
  }

  // ── DOM cache ─────────────────────────────────────────────────────────────────

  function cacheElements() {
    $root = el("axis-chatbot-root");
    $trigger = el("axis-trigger-btn");
    $backdrop = el("axis-backdrop");
    $panel = el("axis-panel");
    $header = el("axis-panel-header");
    $headerTitle = el("axis-header-title");
    $headerSubtitle = el("axis-header-subtitle");
    $btnBack = el("axis-btn-back");
    $btnMinimise = el("axis-btn-minimise");
    $btnClose = el("axis-btn-close");
    $btnLeftToggle = el("axis-btn-left-toggle");
    $btnRightToggle = el("axis-btn-right-toggle");
    $welcomeView = el("axis-welcome-view");
    $welcomeSkeleton = el("axis-welcome-skeleton");
    $welcomeContent = el("axis-welcome-content");
    $welcomeName = el("axis-welcome-name");
    $welcomeTagline = el("axis-welcome-tagline");
    $statCoursesVal = el("axis-stat-courses-val");
    $statBadgesVal = el("axis-stat-badges-val");
    $statStreakVal = el("axis-stat-streak-val");
    $statPointsVal = el("axis-stat-points-val");
    $statPointsLbl = el("axis-stat-points-lbl");
    $threepanel = el("axis-threepanel");
    $leftInner = el("axis-left-inner");
    $subtabs = el("axis-subtabs");
    $chatArea = el("axis-chat-area");
    $messages = el("axis-messages");
    $placeholder = el("axis-chat-placeholder");
    $placeholderText = el("axis-placeholder-text");
    $placeholderIcon = el("axis-placeholder-icon");
    $cmPickerArea = el("axis-cm-picker-area");
    $suggestionChips = el("axis-suggestion-chips");
    $typing = el("axis-typing");
    $analysisArea = el("axis-analysis-area");
    $analysisSkeleton = el("axis-analysis-skeleton");
    $analysisContent = el("axis-analysis-content");
    $inputBar = el("axis-chat-input-bar");
    $chatInput = el("axis-chat-input");
    $sendBtn = el("axis-send-btn");
    $charCount = el("axis-char-count");
    $inputHint = el("axis-input-hint");
    $metricCoursesTotal = el("axis-metric-courses-total");
    $metricCoursesSub = el("axis-metric-courses-sub");
    $metricCertsVal = el("axis-metric-certs-val");
    $metricCertsSub = el("axis-metric-certs-sub");
    $metricPointsVal = el("axis-metric-points-val");
    $metricPointsLbl = el("axis-metric-points-lbl");
    $metricPointsSub = el("axis-metric-points-sub");
    $metricProgressVal = el("axis-metric-progress-val");
    $metricProgressFill = el("axis-metric-progress-fill");
    $analysisGrades = el("axis-analysis-grades");
    $gradeDisplay = el("axis-grade-display");
    $streakBanner = el("axis-streak-banner");
    $streakText = el("axis-streak-text");
    $rightPanel = el("axis-right");
    $rightInner = el("axis-right-inner");
    $leftPanel = el("axis-left");
  }

  // ── Public init ───────────────────────────────────────────────────────────────

  // Runtime CSS injection for the in-course AI Assistant drawer (team QA #7, #10).
  // These rules also live in styles.css, but the aggregated theme CSS bundle can lag
  // behind deploys, so we inject them here (JS refreshes reliably via jsrev) with
  // !important to guarantee they apply.
  function injectAssistantDrawerFixes() {
    if (document.getElementById("axis-assistant-drawer-fix")) { return; }
    const st = document.createElement("style");
    st.id = "axis-assistant-drawer-fix";
    st.textContent =
      "#edzai-student-panel.edzai-drawer{margin:0 !important}" +
      "#edzai-student-panel.edzai-drawer .edzai-chat-messages{max-height:none !important;flex:1 1 auto !important;min-height:120px}";
    (document.head || document.documentElement).appendChild(st);
  }

  const init = (config) => {
    state.config = config || {};
    const ready = () => {
      injectAssistantDrawerFixes();
      cacheElements();
      if (!$root || !$trigger || !$panel) {
        return;
      }
      bindEvents();
    };
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", ready);
    } else {
      ready();
    }
  };
  return { init: init };
});
