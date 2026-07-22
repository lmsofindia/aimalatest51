// mod_edztrackvideo — native HTML5 <video> adapter.
// Provides a TRUE hard forward-lock by clamping currentTime on the seeking event.
define([], function () {
  "use strict";

  /**
   * @param {HTMLVideoElement} videoEl
   * @param {Object} cfg
   * @param {Object} cb  { onStateChange, onTimeUpdate, onEnded, onError }
   */
  function create(videoEl, cfg, cb) {
    let lockEnabled = false;
    let lockMax = 0;
    const SLACK = 0.9;

    const ready = new Promise(function (resolve, reject) {
      if (!videoEl) {
        reject(new Error("no video element"));
        return;
      }
      if (videoEl.readyState >= 1 && videoEl.duration) {
        resolve();
      } else {
        videoEl.addEventListener("loadedmetadata", function () { resolve(); }, { once: true });
        videoEl.addEventListener("error", function (e) { reject(e); }, { once: true });
      }
    });

    // Wire native events to normalized callbacks.
    videoEl.addEventListener("play", function () { cb.onStateChange && cb.onStateChange("playing"); });
    videoEl.addEventListener("pause", function () { cb.onStateChange && cb.onStateChange("paused"); });
    videoEl.addEventListener("ended", function () { cb.onEnded && cb.onEnded(); });
    videoEl.addEventListener("waiting", function () { cb.onStateChange && cb.onStateChange("buffering"); });
    videoEl.addEventListener("timeupdate", function () {
      cb.onTimeUpdate && cb.onTimeUpdate(videoEl.currentTime);
    });
    videoEl.addEventListener("error", function (e) { cb.onError && cb.onError(e); });

    // TRUE hard lock: clamp on every seeking attempt.
    videoEl.addEventListener("seeking", function () {
      if (lockEnabled && videoEl.currentTime > lockMax + SLACK) {
        try { videoEl.currentTime = lockMax; } catch (e) { /* ignore */ }
      }
    });

    // Block native context menu / download.
    videoEl.addEventListener("contextmenu", function (e) { e.preventDefault(); });

    return {
      ready: ready,
      play: function () { const p = videoEl.play(); if (p && p.catch) { p.catch(function () {}); } },
      pause: function () { videoEl.pause(); },
      getCurrentTime: function () { return videoEl.currentTime || 0; },
      getDuration: function () { return videoEl.duration || 0; },
      seekTo: function (sec) { try { videoEl.currentTime = sec; } catch (e) {} },
      setForwardLock: function (enabled, maxSec) { lockEnabled = !!enabled; lockMax = Number(maxSec) || 0; },
      setVolume: function (v) { videoEl.volume = Math.max(0, Math.min(1, v / 100)); },
      mute: function () { videoEl.muted = true; },
      unmute: function () { videoEl.muted = false; },
      isMuted: function () { return videoEl.muted; },
      setRate: function (r) { try { videoEl.playbackRate = r; } catch (e) {} },
      getBuffered: function () {
        try {
          if (videoEl.buffered && videoEl.buffered.length) {
            return videoEl.buffered.end(videoEl.buffered.length - 1);
          }
        } catch (e) {}
        return 0;
      },
      toggleCaptions: function () { return false; }, // No track captions in v1.
      getFullscreenTarget: function () { return videoEl.closest(".edztrackvideo-player") || videoEl; },
      isPlaying: function () { return !videoEl.paused && !videoEl.ended; },
      destroy: function () { try { videoEl.pause(); } catch (e) {} },
    };
  }

  return { create: create };
});
