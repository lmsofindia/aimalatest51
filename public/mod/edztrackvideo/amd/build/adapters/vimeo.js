// mod_edztrackvideo — Vimeo Player SDK adapter.
// Vimeo's API is promise-based, so current time / duration are cached from events
// and returned synchronously for the core's polling loop.
// Forward-lock is detect-and-rewind (enforced by the core's poll).
define([], function () {
  "use strict";

  let sdkLoading = false;
  let sdkReady = false;
  const pending = [];

  function loadSdk(cb) {
    if (sdkReady || window.Vimeo) { sdkReady = true; return cb(); }
    pending.push(cb);
    if (sdkLoading) { return; }
    sdkLoading = true;
    const s = document.createElement("script");
    s.id = "edztv-vimeo-sdk";
    s.src = "https://player.vimeo.com/api/player.js";
    s.async = true;
    s.onload = function () { sdkReady = true; while (pending.length) { pending.shift()(); } };
    s.onerror = function () { while (pending.length) { pending.shift()(); } };
    document.head.appendChild(s);
    setTimeout(function () { if (!sdkReady) { while (pending.length) { pending.shift()(); } } }, 10000);
  }

  function create(holderEl, cfg, cb) {
    let player = null;
    let cachedTime = 0;
    let cachedDuration = 0;
    let lastVolume = 100;
    let captionsOn = false;

    const ready = new Promise(function (resolve, reject) {
      loadSdk(function () {
        if (!window.Vimeo) { reject(new Error("Vimeo SDK unavailable")); return; }
        try {
          const opts = { id: Number(cfg.videoid), controls: false, playsinline: true, dnt: true };
          if (cfg.videohash) { opts.h = cfg.videohash; }
          player = new window.Vimeo.Player(holderEl, opts);

          player.on("play", function () { cb.onStateChange && cb.onStateChange("playing"); });
          player.on("pause", function () { cb.onStateChange && cb.onStateChange("paused"); });
          player.on("bufferstart", function () { cb.onStateChange && cb.onStateChange("buffering"); });
          player.on("ended", function () { cb.onEnded && cb.onEnded(); });
          player.on("error", function (e) { cb.onError && cb.onError(e); });
          player.on("timeupdate", function (data) {
            cachedTime = data.seconds || 0;
            if (data.duration) { cachedDuration = data.duration; }
            cb.onTimeUpdate && cb.onTimeUpdate(cachedTime);
          });
          player.on("seeked", function (data) { cachedTime = data.seconds || 0; });

          player.ready().then(function () {
            return player.getDuration();
          }).then(function (d) {
            cachedDuration = d || 0;
            resolve();
          }).catch(function (e) { reject(e); });
        } catch (e) { reject(e); }
      });
    });

    return {
      ready: ready,
      play: function () { try { player.play().catch(function () {}); } catch (e) {} },
      pause: function () { try { player.pause(); } catch (e) {} },
      getCurrentTime: function () { return cachedTime; },
      getDuration: function () { return cachedDuration; },
      seekTo: function (sec) { try { player.setCurrentTime(sec); cachedTime = sec; } catch (e) {} },
      setForwardLock: function () { /* no-op: core poll enforces */ },
      setVolume: function (v) { lastVolume = v; try { player.setVolume(Math.max(0, Math.min(1, v / 100))); } catch (e) {} },
      mute: function () { try { player.setVolume(0); } catch (e) {} },
      unmute: function () { try { player.setVolume(Math.max(0.01, lastVolume / 100)); } catch (e) {} },
      isMuted: function () { return lastVolume === 0; },
      setRate: function (r) { try { player.setPlaybackRate(r).catch(function () {}); } catch (e) {} },
      getBuffered: function () { return 0; },
      toggleCaptions: function (on) {
        captionsOn = (typeof on === "boolean") ? on : !captionsOn;
        try {
          if (captionsOn) { player.enableTextTrack("en").catch(function () {}); }
          else { player.disableTextTrack().catch(function () {}); }
          return captionsOn;
        } catch (e) { return false; }
      },
      getFullscreenTarget: function () { return holderEl.closest(".edztrackvideo-player") || holderEl; },
      isPlaying: function () { return false; }, // state tracked via events in core
      destroy: function () { try { player && player.destroy(); } catch (e) {} },
    };
  }

  return { create: create };
});
