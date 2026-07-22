// mod_edztrackvideo — provider-agnostic player core.
// Owns: UI, progress tracking, forward-lock policy, resume, completion reporting,
// and speed policy. Talks to YouTube / Vimeo / HTML5 only through an adapter.
define(["core/ajax"], function (Ajax) {
  "use strict";

  const POLL_MS = 800;
  const PERSIST_MS = 8000;
  const MIN_PERSIST_SEC = 0.5;
  const EPSILON = 1.2;
  const JUMP_TOLERANCE = 1.5; // slack over expected natural advance
  const SEEK_SLACK = 0.9;

  function log() { try { console.debug.apply(console, ["[edztrackvideo]"].concat([].slice.call(arguments))); } catch (e) {} }

  function callWs(method, args) {
    try { return Ajax.call([{ methodname: method, args: args }])[0]; }
    catch (e) { return Promise.reject(e); }
  }

  function hms(d) {
    d = Number(d);
    if (isNaN(d) || d < 0) { return "00:00"; }
    const h = Math.floor(d / 3600), m = Math.floor((d % 3600) / 60), s = Math.floor(d % 60);
    const mm = String(m).padStart(2, "0"), ss = String(s).padStart(2, "0");
    return h > 0 ? String(h).padStart(2, "0") + ":" + mm + ":" + ss : mm + ":" + ss;
  }

  function el(id) { return document.getElementById(id); }

  function initOne(config) {
    const edid = Number(config.edztrackvideoid);
    if (!edid) { log("missing edid", config); return; }

    const sourcetype = config.sourcetype || "youtube";

    // DOM refs.
    const wrapper = el("edztrackvideo-player-" + edid);
    if (!wrapper) { log("no wrapper"); return; }
    const mediaHolder = el("edztrackvideo-media-" + edid);     // yt/vimeo
    const videoEl = el("edztrackvideo-video-" + edid);          // upload
    const overlayMsg = el("edztrackvideo-overlay-message-" + edid);
    const loadingEl = el("edztrackvideo-loading-" + edid);
    const errorEl = el("edztrackvideo-error-" + edid);
    const bigPlay = el("edz-bigplay-" + edid);
    const playBtn = el("edz-play-" + edid);
    const muteBtn = el("edz-mute-" + edid);
    const volRange = el("edz-volume-" + edid);
    const timeLabel = el("edz-time-" + edid);
    const progWrap = el("edz-progress-wrap-" + edid);
    const fillBar = el("edz-progress-" + edid);
    const bufferBar = el("edz-buffer-" + edid);
    const lockedBar = el("edz-locked-" + edid);
    const marker = el("edz-marker-" + edid);
    const handle = el("edz-handle-" + edid);
    const ccBtn = el("edz-cc-toggle-" + edid);
    const speedSel = el("edz-speed-" + edid);
    const fullBtn = el("edz-full-" + edid);
    const ringFg = el("edz-ring-fg-" + edid);
    const ringCheck = el("edz-ring-check-" + edid);
    const resumeBar = el("edz-resumebar-" + edid);
    const resumeBtn = el("edz-resume-" + edid);
    const resumeLabel = el("edz-resume-label-" + edid);
    const dismissBtn = el("edz-dismiss-" + edid);

    // State.
    let adapter = null;
    let duration = 0;
    let maxSeen = 0;
    let pollTimer = null;
    let lastPersist = 0;
    let lastTickTs = null;
    let lastTickPos = null;
    let serverProgress = {
      last_position: 0, maxwatched: 0, duration: 0, completed: 0,
      show_resume: 0, no_forward_seek_first_view: config.no_forward_seek_first_view ? 1 : 0,
    };

    function toast(msg, ms) {
      if (!overlayMsg) { return; }
      overlayMsg.textContent = msg;
      overlayMsg.style.display = "block";
      clearTimeout(overlayMsg._t);
      overlayMsg._t = setTimeout(function () { overlayMsg.style.display = "none"; }, ms || 2200);
    }

    function isForwardLocked() {
      const flagOn = Boolean(config.no_forward_seek_first_view) ||
        Boolean(Number(serverProgress.no_forward_seek_first_view || 0));
      return flagOn && !Number(serverProgress.completed || 0);
    }

    function applyLockToAdapter() {
      if (adapter && adapter.setForwardLock) { adapter.setForwardLock(isForwardLocked(), maxSeen); }
      if (wrapper) { wrapper.classList.toggle("edz-locked", isForwardLocked()); }
    }

    // ---- speed policy ----
    function effectiveRates() {
      if (!config.allow_speed) { return [1]; }
      const cap = Number(config.max_speed) || 1;
      return [0.75, 1, 1.25, 1.5, 1.75, 2].filter(function (r) { return r <= cap + 0.001; });
    }
    function buildSpeedSelector() {
      if (!speedSel) { return; }
      const rates = effectiveRates();
      if (rates.length <= 1) { speedSel.style.display = "none"; return; }
      speedSel.innerHTML = "";
      rates.forEach(function (r) {
        const o = document.createElement("option");
        o.value = String(r);
        o.textContent = r + "x";
        if (r === 1) { o.selected = true; }
        speedSel.appendChild(o);
      });
      speedSel.addEventListener("change", function () {
        const r = Math.min(Number(speedSel.value) || 1, Number(config.max_speed) || 1);
        adapter && adapter.setRate && adapter.setRate(r);
      });
    }

    // ---- UI ----
    function pct(n, d) { return d > 0 ? Math.max(0, Math.min(100, (n / d) * 100)) : 0; }

    function updateUI(current) {
      const p = pct(current, duration);
      const mp = pct(maxSeen, duration);
      if (fillBar) { fillBar.style.width = p + "%"; }
      if (handle) { handle.style.left = p + "%"; }
      if (marker) { marker.style.left = mp + "%"; marker.style.display = mp > 0 ? "block" : "none"; }
      if (lockedBar) {
        if (isForwardLocked()) {
          lockedBar.style.display = "block";
          lockedBar.style.left = mp + "%";
          lockedBar.style.width = Math.max(0, 100 - mp) + "%";
        } else {
          lockedBar.style.display = "none";
        }
      }
      if (timeLabel) { timeLabel.textContent = hms(current) + " / " + hms(duration); }
      if (progWrap) { progWrap.setAttribute("aria-valuenow", Math.floor(p)); }
      updateRing();
    }

    function updateBuffer() {
      if (!bufferBar || !adapter || !adapter.getBuffered) { return; }
      const b = adapter.getBuffered() || 0;
      bufferBar.style.width = pct(b, duration) + "%";
    }

    function updateRing() {
      const threshold = Number(config.completion_threshold) || 100;
      const watched = pct(maxSeen, duration);
      const ratio = Math.max(0, Math.min(100, (watched / threshold) * 100));
      if (ringFg) { ringFg.setAttribute("stroke-dasharray", ratio.toFixed(1) + ",100"); }
      const done = Number(serverProgress.completed || 0) || watched >= threshold;
      if (ringCheck) { ringCheck.hidden = !done; }
      if (done && wrapper) { wrapper.classList.add("edz-complete"); }
    }

    function setPlayingUI(playing) {
      if (playBtn) {
        const pi = playBtn.querySelector(".edz-ic-play"), pa = playBtn.querySelector(".edz-ic-pause");
        if (pi) { pi.hidden = playing; }
        if (pa) { pa.hidden = !playing; }
      }
      if (bigPlay) { bigPlay.style.display = playing ? "none" : "flex"; }
      wrapper.classList.toggle("edz-playing", playing);
    }

    // ---- progress persistence ----
    function persist(posInput, force) {
      const pos = Number(posInput);
      if (isNaN(pos) || pos <= MIN_PERSIST_SEC) { return; }
      const now = Date.now();
      if (!force && now - lastPersist < PERSIST_MS) { return; }
      lastPersist = now;
      callWs("mod_edztrackvideo_update_progress", {
        edztrackvideoid: edid, current_time: pos, duration: duration,
      }).then(function (res) {
        const r = Array.isArray(res) ? res[0] : res;
        if (!r) { return; }
        serverProgress = Object.assign(serverProgress, r);
        maxSeen = Math.max(maxSeen, Number(r.maxwatched || 0));
        if (r.completed) { applyLockToAdapter(); toastOnce(); }
        updateRing();
      }).catch(function (e) { log("persist failed", e); });
    }

    let completeToasted = false;
    function toastOnce() { if (!completeToasted) { completeToasted = true; toast(M.util.get_string("completedmsg", "mod_edztrackvideo")); } }

    function flushBeacon(pos) {
      if (isNaN(pos) || pos <= MIN_PERSIST_SEC) { return; }
      try {
        const root = (window.M && M.cfg && M.cfg.wwwroot) || "";
        const sk = config.sesskey || (window.M && M.cfg ? M.cfg.sesskey : "");
        if (navigator.sendBeacon && root && sk) {
          const url = root + "/lib/ajax/service.php?sesskey=" + encodeURIComponent(sk) +
            "&info=mod_edztrackvideo_update_progress";
          const payload = JSON.stringify([{
            index: 0, methodname: "mod_edztrackvideo_update_progress",
            args: { edztrackvideoid: edid, current_time: pos, duration: duration },
          }]);
          if (navigator.sendBeacon(url, new Blob([payload], { type: "application/json" }))) { return; }
        }
      } catch (e) { log("beacon failed", e); }
      persist(pos, true);
    }

    // ---- tracking poll: seek-vs-playback detection ----
    function startTracking() {
      if (pollTimer) { return; }
      pollTimer = setInterval(function () {
        if (!adapter) { return; }
        let pos = adapter.getCurrentTime();
        if (isNaN(pos)) { return; }
        duration = adapter.getDuration() || duration;

        const nowTs = Date.now();
        const elapsed = lastTickTs != null ? (nowTs - lastTickTs) / 1000 : 0;
        const lastPos = lastTickPos != null ? lastTickPos : pos;
        const advance = pos - lastPos;
        lastTickTs = nowTs; lastTickPos = pos;

        const forwardSeek = pos > maxSeen + EPSILON && advance > elapsed + JUMP_TOLERANCE;
        if (forwardSeek && isForwardLocked()) {
          const backTo = Math.max(Number(serverProgress.maxwatched || 0), maxSeen);
          adapter.seekTo(backTo);
          lastTickPos = backTo;
          toast(M.util.get_string("forwardlocked", "mod_edztrackvideo"));
          return;
        }

        if (pos > maxSeen) { maxSeen = pos; applyLockToAdapter(); }
        updateUI(pos);
        updateBuffer();
        persist(pos, false);
      }, POLL_MS);
    }
    function stopTracking() { if (pollTimer) { clearInterval(pollTimer); pollTimer = null; } }

    // ---- progress bar seeking ----
    function seekFromClick(clientX) {
      if (!progWrap) { return; }
      const rect = progWrap.getBoundingClientRect();
      const ratio = Math.max(0, Math.min(1, (clientX - rect.left) / rect.width));
      const target = duration * ratio;
      const allowed = !isForwardLocked() ||
        target <= Math.max(Number(serverProgress.maxwatched || 0), maxSeen) + SEEK_SLACK;
      if (!allowed) {
        toast(M.util.get_string("forwardlocked", "mod_edztrackvideo"));
        return;
      }
      adapter.seekTo(target);
      updateUI(target);
    }

    function wireControls() {
      function togglePlay() {
        if (!adapter) { return; }
        if (wrapper.classList.contains("edz-playing")) { adapter.pause(); }
        else { adapter.play(); }
      }
      playBtn && playBtn.addEventListener("click", function (e) { e.stopPropagation(); togglePlay(); });
      bigPlay && bigPlay.addEventListener("click", function (e) { e.stopPropagation(); togglePlay(); });

      muteBtn && muteBtn.addEventListener("click", function (e) {
        e.stopPropagation();
        if (!adapter) { return; }
        const muted = adapter.isMuted();
        if (muted) { adapter.unmute(); } else { adapter.mute(); }
        const v = muteBtn.querySelector(".edz-ic-vol"), m = muteBtn.querySelector(".edz-ic-muted");
        if (v) { v.hidden = !muted ? true : false; }
        if (m) { m.hidden = muted ? true : false; }
      });

      volRange && volRange.addEventListener("input", function () {
        adapter && adapter.setVolume(Number(volRange.value));
      });

      progWrap && progWrap.addEventListener("click", function (e) { seekFromClick(e.clientX); });
      progWrap && progWrap.addEventListener("keydown", function (e) {
        if (!adapter) { return; }
        const cur = adapter.getCurrentTime();
        if (e.key === "ArrowRight" || e.key === "ArrowUp") {
          e.preventDefault();
          const step = Math.max(5, duration * 0.02);
          const target = cur + step;
          if (isForwardLocked() && target > maxSeen + SEEK_SLACK) {
            toast(M.util.get_string("forwardlocked", "mod_edztrackvideo")); return;
          }
          adapter.seekTo(target);
        } else if (e.key === "ArrowLeft" || e.key === "ArrowDown") {
          e.preventDefault();
          adapter.seekTo(Math.max(0, cur - 5));
        } else if (e.key === " " || e.key === "Spacebar") {
          e.preventDefault(); togglePlay();
        }
      });

      ccBtn && ccBtn.addEventListener("click", function (e) {
        e.stopPropagation();
        if (!adapter || !adapter.toggleCaptions) { return; }
        const on = adapter.toggleCaptions();
        ccBtn.setAttribute("aria-pressed", on ? "true" : "false");
        ccBtn.classList.toggle("active", on);
      });

      fullBtn && fullBtn.addEventListener("click", function (e) {
        e.stopPropagation();
        // Always fullscreen the player wrapper so the custom controls stay visible
        // (the YT holder is detached after the API swaps in its iframe).
        const target = wrapper;
        const fsEl = document.fullscreenElement || document.webkitFullscreenElement;
        if (fsEl) {
          (document.exitFullscreen || document.webkitExitFullscreen).call(document);
        } else {
          (target.requestFullscreen || target.webkitRequestFullscreen || function () {}).call(target);
        }
      });
      document.addEventListener("fullscreenchange", function () {
        wrapper.classList.toggle("edz-fullscreen", !!document.fullscreenElement);
      });

      wrapper.addEventListener("keydown", function (e) {
        if (!isForwardLocked()) { return; }
        if (["ArrowRight", "ArrowUp", "PageUp", "End"].indexOf(e.key) !== -1) {
          e.preventDefault();
          toast(M.util.get_string("forwardlocked", "mod_edztrackvideo"));
        }
      });
      wrapper.addEventListener("contextmenu", function (e) { e.preventDefault(); });
    }

    function wireResume() {
      if (!resumeBar) { return; }
      if (serverProgress.show_resume && Number(serverProgress.maxwatched || 0) > MIN_PERSIST_SEC) {
        resumeBar.style.display = "flex";
        if (resumeLabel) {
          resumeLabel.textContent = M.util.get_string("resumefrom", "mod_edztrackvideo",
            hms(serverProgress.maxwatched));
        }
        resumeBtn && (resumeBtn.onclick = function (ev) {
          ev.stopPropagation();
          const to = Math.max(0, Number(serverProgress.maxwatched || 0) - 2);
          adapter && adapter.seekTo(to);
          adapter && adapter.play();
          resumeBar.style.display = "none";
        });
        dismissBtn && (dismissBtn.onclick = function () {
          resumeBar.style.display = "none";
          callWs("mod_edztrackvideo_mark_dismissed", { edztrackvideoid: edid }).catch(function () {});
        });
      } else {
        resumeBar.style.display = "none";
      }
    }

    // ---- adapter callbacks ----
    const callbacks = {
      onStateChange: function (state) {
        if (state === "playing") { setPlayingUI(true); if (loadingEl) { loadingEl.style.display = "none"; } }
        else if (state === "paused") { setPlayingUI(false); }
        else if (state === "buffering") { /* optional spinner */ }
      },
      onTimeUpdate: function () { /* core polls; nothing required here */ },
      onEnded: function () {
        setPlayingUI(false);
        const pos = adapter ? adapter.getCurrentTime() : duration;
        persist(Math.max(pos, duration), true);
      },
      onError: function (e) {
        log("adapter error", e);
        if (loadingEl) { loadingEl.style.display = "none"; }
        if (errorEl) { errorEl.style.display = "block"; }
      },
    };

    // ---- bootstrap ----
    function bootstrapAdapter() {
      // Upload, server file and direct URL all play through the native HTML5 adapter.
      const html5types = ["upload", "serverfile", "directurl"];
      const isHtml5 = html5types.indexOf(sourcetype) !== -1;
      const holder = isHtml5 ? videoEl : mediaHolder;
      const modName = "mod_edztrackvideo/adapters/" +
        (sourcetype === "vimeo" ? "vimeo" : isHtml5 ? "html5" : "youtube");

      require([modName], function (mod) {
        try { adapter = mod.create(holder, config, callbacks); }
        catch (e) { callbacks.onError(e); return; }

        adapter.ready.then(function () {
          if (loadingEl) { loadingEl.style.display = "none"; }
          duration = adapter.getDuration() || 0;
          if (volRange) { adapter.setVolume(Number(volRange.value || 100)); }
          buildSpeedSelector();
          applyLockToAdapter();
          updateUI(0);
          updateRing();
          wireResume();
          startTracking();
        }).catch(function (e) { callbacks.onError(e); });
      });
    }

    // Seed from server, then start the adapter.
    callWs("mod_edztrackvideo_get_progress", { edztrackvideoid: edid }).then(function (res) {
      const r = Array.isArray(res) ? res[0] : res;
      if (r) {
        ["last_position", "maxwatched", "duration", "completed", "show_resume",
          "last_viewed_on", "no_forward_seek_first_view"].forEach(function (k) {
          r[k] = Number(r[k] || 0);
        });
        serverProgress = r;
        maxSeen = Math.max(maxSeen, serverProgress.maxwatched || 0);
      }
    }).catch(function (e) { log("get_progress failed", e); })
      .then(function () { wireControls(); bootstrapAdapter(); });

    // Exit flush.
    function onExit() {
      stopTracking();
      if (adapter) {
        const pos = adapter.getCurrentTime();
        if (!isNaN(pos) && pos > MIN_PERSIST_SEC) { flushBeacon(pos); }
      }
    }
    window.addEventListener("pagehide", onExit);
    window.addEventListener("beforeunload", onExit);
    document.addEventListener("visibilitychange", function () {
      if (document.visibilityState === "hidden" && adapter) {
        const pos = adapter.getCurrentTime();
        if (!isNaN(pos) && pos > MIN_PERSIST_SEC) { flushBeacon(pos); }
      }
    });
  }

  return {
    init: function (arg) {
      try {
        // Object config (normal path).
        if (arg && typeof arg === "object" && !Array.isArray(arg) && arg.edztrackvideoid) {
          initOne(arg);
          return;
        }
        if (Array.isArray(arg) && arg.length && typeof arg[0] === "object") {
          initOne(arg[0]);
          return;
        }
        // Numeric id fallback -> fetch config from server.
        const edid = typeof arg === "number" ? arg : (Array.isArray(arg) ? Number(arg[0]) : 0);
        if (!edid) { log("init: unusable arg", arg); return; }
        callWs("mod_edztrackvideo_get_config", { edztrackvideoid: edid }).then(function (res) {
          const r = Array.isArray(res) ? res[0] : res;
          if (!r) { throw new Error("empty config"); }
          r.sesskey = (window.M && M.cfg) ? M.cfg.sesskey : null;
          initOne(r);
        }).catch(function (e) { log("get_config failed", e); });
      } catch (e) { log("init error", e); }
    },
  };
});
