/**
 * AMD: block_corpusercmplchart/corpusercmplchart
 * Draws an animated multi-ring gauge on <canvas> using native Canvas 2D API.
 * No external dependencies — avoids CDN restrictions.
 */
define([], function() {

    // Ring config: outermost first.
    // grad1/grad2 are CSS-style colour stops for a linear gradient (left→right).
    var RINGS = [
        { key: 'avgpct',  grad1: '#ec4899', grad2: '#a855f7', lineWidth: 18, radiusFactor: 0.82 },
        { key: 'quizpct', grad1: '#38bdf8', grad2: '#6366f1', lineWidth: 14, radiusFactor: 0.62 },
        { key: 'modpct',  grad1: '#2dd4bf', grad2: '#22c55e', lineWidth: 14, radiusFactor: 0.42 },
    ];

    function GaugeBlock(instanceid, data) {
        this.instanceid = instanceid;
        this.data       = data;         // { avgpct, quizpct, modpct }
        this.wrap       = document.querySelector('[data-instanceid="' + instanceid + '"]');
        this.canvas     = this.wrap && this.wrap.querySelector('[data-region="cucc-canvas"]');
        this.overlay    = this.wrap && this.wrap.querySelector('[data-region="cucc-overlay"]');
        this._raf       = null;
        this._progress  = 0;   // animation 0→1
    }

    GaugeBlock.prototype.init = function() {
        if (!this.canvas) { return; }
        var self    = this;
        var dpr     = window.devicePixelRatio || 1;
        var size    = 220;

        self.canvas.width  = size * dpr;
        self.canvas.height = size * dpr;
        self.canvas.style.width  = size + 'px';
        self.canvas.style.height = size + 'px';
        self.ctx = self.canvas.getContext('2d');
        self.ctx.scale(dpr, dpr);
        self.cx = size / 2;
        self.cy = size / 2;
        self.size = size;

        // Animate rings from 0 to full.
        var start  = null;
        var DURATION = 1000; // ms

        function step(ts) {
            if (!start) { start = ts; }
            self._progress = Math.min(1, (ts - start) / DURATION);
            // Ease-out cubic.
            var ease = 1 - Math.pow(1 - self._progress, 3);
            self.draw(ease);
            if (self._progress < 1) {
                self._raf = requestAnimationFrame(step);
            }
        }
        self._raf = requestAnimationFrame(step);

        // Position the centre overlay div.
        self.positionOverlay();
        window.addEventListener('resize', function() { self.positionOverlay(); });
    };

    GaugeBlock.prototype.positionOverlay = function() {
        if (!this.overlay) { return; }
        this.overlay.style.left = this.cx + 'px';
        this.overlay.style.top  = this.cy + 'px';
    };

    GaugeBlock.prototype.draw = function(ease) {
        var ctx  = this.ctx;
        var cx   = this.cx;
        var cy   = this.cy;
        var self = this;

        ctx.clearRect(0, 0, self.size, self.size);

        RINGS.forEach(function(ring) {
            var r         = self.size / 2 * ring.radiusFactor;
            var pct       = (self.data[ring.key] || 0) / 100 * ease;
            var startAngle = -Math.PI / 2;
            var endAngle   = startAngle + Math.PI * 2 * pct;

            // Background track ring.
            ctx.beginPath();
            ctx.arc(cx, cy, r, 0, Math.PI * 2);
            ctx.strokeStyle = 'rgba(255,255,255,0.07)';
            ctx.lineWidth   = ring.lineWidth;
            ctx.stroke();

            if (pct <= 0) { return; }

            // Foreground arc with gradient.
            var grad = ctx.createLinearGradient(cx - r, cy, cx + r, cy);
            grad.addColorStop(0, ring.grad1);
            grad.addColorStop(1, ring.grad2);

            ctx.beginPath();
            ctx.arc(cx, cy, r, startAngle, endAngle);
            ctx.strokeStyle = grad;
            ctx.lineWidth   = ring.lineWidth;
            ctx.lineCap     = 'round';
            ctx.stroke();
        });
    };

    return {
        init: function(opts) {
            var b = new GaugeBlock(opts.instanceid, {
                avgpct:  opts.avgpct  || 0,
                quizpct: opts.quizpct || 0,
                modpct:  opts.modpct  || 0,
            });
            b.init();
        }
    };
});
