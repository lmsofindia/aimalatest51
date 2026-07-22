// mod_edztrackvideo — YouTube IFrame API adapter.
// Forward-lock here is detect-and-rewind (enforced by the core's poll), since the
// YouTube API only lets us correct a seek after it happens.
define([], function () {
  "use strict";

  let apiLoading = false;
  let apiReady = false;
  const pending = [];

  function loadApi(cb) {
    if (apiReady || (window.YT && window.YT.Player)) { apiReady = true; return cb(); }
    pending.push(cb);
    if (apiLoading) { return; }
    apiLoading = true;
    if (!document.getElementById("edztv-yt-api")) {
      const s = document.createElement("script");
      s.id = "edztv-yt-api";
      s.src = "https://www.youtube.com/iframe_api";
      s.async = true;
      document.head.appendChild(s);
    }
    const prev = window.onYouTubeIframeAPIReady;
    window.onYouTubeIframeAPIReady = function () {
      if (typeof prev === "function") { try { prev(); } catch (e) {} }
      apiReady = true;
      while (pending.length) { pending.shift()(); }
    };
    setTimeout(function () {
      if (!apiReady) { while (pending.length) { pending.shift()(); } }
    }, 10000);
  }

  function create(holderEl, cfg, cb) {
    let player = null;
    let captionsOn = false;

    const ready = new Promise(function (resolve, reject) {
      loadApi(function () {
        if (!(window.YT && window.YT.Player)) { reject(new Error("YT API unavailable")); return; }
        try {
          player = new window.YT.Player(holderEl, {
            videoId: cfg.videoid,
            playerVars: {
              enablejsapi: 1, rel: 0, modestbranding: 1, controls: 0,
              iv_load_policy: 3, cc_load_policy: 1, cc_lang_pref: "en", playsinline: 1,
            },
            events: {
              onReady: function () { resolve(); },
              onError: function (e) { cb.onError && cb.onError(e); },
              onStateChange: function (evt) {
                const YT = window.YT;
                switch (evt.data) {
                  case YT.PlayerState.PLAYING: cb.onStateChange && cb.onStateChange("playing"); break;
                  case YT.PlayerState.PAUSED: cb.onStateChange && cb.onStateChange("paused"); break;
                  case YT.PlayerState.BUFFERING: cb.onStateChange && cb.onStateChange("buffering"); break;
                  case YT.PlayerState.ENDED: cb.onEnded && cb.onEnded(); break;
                  default: break;
                }
              },
            },
          });
        } catch (e) { reject(e); }
      });
    });

    return {
      ready: ready,
      play: function () { try { player.playVideo(); } catch (e) {} },
      pause: function () { try { player.pauseVideo(); } catch (e) {} },
      getCurrentTime: function () { try { return player.getCurrentTime() || 0; } catch (e) { return 0; } },
      getDuration: function () { try { return player.getDuration() || 0; } catch (e) { return 0; } },
      seekTo: function (sec) { try { player.seekTo(sec, true); } catch (e) {} },
      setForwardLock: function () { /* no-op: core poll enforces */ },
      setVolume: function (v) { try { player.setVolume(v); } catch (e) {} },
      mute: function () { try { player.mute(); } catch (e) {} },
      unmute: function () { try { player.unMute(); } catch (e) {} },
      isMuted: function () { try { return player.isMuted(); } catch (e) { return false; } },
      setRate: function (r) { try { player.setPlaybackRate(r); } catch (e) {} },
      getBuffered: function () {
        try { return (player.getVideoLoadedFraction() || 0) * (player.getDuration() || 0); } catch (e) { return 0; }
      },
      toggleCaptions: function (on) {
        captionsOn = (typeof on === "boolean") ? on : !captionsOn;
        try {
          if (player && typeof player.loadModule === "function") { player.loadModule("captions"); }
          if (captionsOn) { player.setOption("captions", "track", { languageCode: "en" }); }
          else { player.setOption("captions", "track", {}); }
          return captionsOn;
        } catch (e) { return false; }
      },
      getFullscreenTarget: function () { return holderEl.closest(".edztrackvideo-player") || holderEl; },
      isPlaying: function () {
        try { return player.getPlayerState() === window.YT.PlayerState.PLAYING; } catch (e) { return false; }
      },
      destroy: function () { try { player && player.destroy(); } catch (e) {} },
    };
  }

  return { create: create };
});
