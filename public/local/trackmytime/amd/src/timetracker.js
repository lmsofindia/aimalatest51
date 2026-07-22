// =============================================================================
// GemUI Timetracker — heartbeat AMD module
// Records time-on-page by calling local_trackmytime_record_session
// every HEARTBEAT_INTERVAL seconds while the tab is active.
//
// Usage (called from theme lib.php or layout PHP):
//   $PAGE->requires->js_call_amd('local_trackmytime/timetracker', 'init', [$cmid]);
// =============================================================================

define([
    'core/ajax',
    'core/log'
], function(Ajax, Log) {

    'use strict';

    var HEARTBEAT_INTERVAL = 60;   // seconds between server pings
    var MIN_ACTIVE_SECS    = 5;    // ignore sessions shorter than this
    var MAX_SESSION_SECS   = 3600; // cap per WS call

    var _cmid       = 0;
    var _startTime  = 0;
    var _lastPing   = 0;
    var _active     = true;
    var _timer      = null;

    /** Send accumulated seconds to server */
    var sendSession = function(seconds) {
        if (seconds < MIN_ACTIVE_SECS || _cmid <= 0) { return; }
        var secs = Math.min(Math.round(seconds), MAX_SESSION_SECS);
        Ajax.call([{
            methodname: 'local_trackmytime_record_session',
            args: {
                cmid:      _cmid,
                timespent: secs,
                timestart: _lastPing
            }
        }])[0].then(function(resp) {
            if (resp && resp.success) {
                Log.debug('GemUI timetracker: recorded ' + secs + 's for cmid ' + _cmid);
            }
        }).catch(function(e) {
            Log.warn('GemUI timetracker: record failed', e);
        });
    };

    /** Tick — fires every HEARTBEAT_INTERVAL seconds */
    var tick = function() {
        if (!_active) { return; }
        var now = Math.floor(Date.now() / 1000);
        var elapsed = now - _lastPing;
        if (elapsed > 0 && elapsed <= HEARTBEAT_INTERVAL + 10) {
            sendSession(elapsed);
        }
        _lastPing = now;
    };

    /** Visibility change handler — pause when tab hidden */
    var onVisibility = function() {
        if (document.hidden) {
            _active = false;
            // Send any pending time before going hidden
            var now = Math.floor(Date.now() / 1000);
            sendSession(now - _lastPing);
            _lastPing = now;
        } else {
            _active  = true;
            _lastPing = Math.floor(Date.now() / 1000);
        }
    };

    /** Send final chunk on page unload */
    var onUnload = function() {
        var now = Math.floor(Date.now() / 1000);
        var elapsed = now - _lastPing;
        if (elapsed >= MIN_ACTIVE_SECS) {
            // Use sendBeacon for reliability during unload
            var data = JSON.stringify({
                cmid:      _cmid,
                timespent: Math.min(elapsed, MAX_SESSION_SECS),
                timestart: _lastPing
            });
            // Fallback to sync XHR if needed — but sendBeacon preferred
            if (navigator.sendBeacon) {
                var url = M.cfg.wwwroot + '/lib/ajax/service.php?sesskey=' + M.cfg.sesskey;
                var body = JSON.stringify([{
                    index: 0,
                    methodname: 'local_trackmytime_record_session',
                    args: {cmid: _cmid, timespent: Math.min(elapsed, MAX_SESSION_SECS), timestart: _lastPing}
                }]);
                navigator.sendBeacon(url, new Blob([body], {type: 'application/json'}));
            }
        }
    };

    return {
        /**
         * Initialise the heartbeat tracker.
         * @param {number} cmid  Course module ID of the current page (>0).
         */
        init: function(cmid) {
            _cmid = parseInt(cmid, 10) || 0;
            if (_cmid <= 0) {
                Log.debug('GemUI timetracker: no cmid, skipping');
                return;
            }

            _startTime = Math.floor(Date.now() / 1000);
            _lastPing  = _startTime;

            // Tab visibility
            document.addEventListener('visibilitychange', onVisibility);
            window.addEventListener('pagehide',  onUnload);
            window.addEventListener('beforeunload', onUnload);

            // Heartbeat timer
            _timer = setInterval(tick, HEARTBEAT_INTERVAL * 1000);

            Log.debug('GemUI timetracker: started for cmid ' + _cmid);
        }
    };
});
