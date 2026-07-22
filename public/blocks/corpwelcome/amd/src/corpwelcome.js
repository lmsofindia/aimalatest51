define(['core/ajax', 'core/templates', 'core/notification'], function(Ajax, Templates, Notification) {

    var DELAY_MS  = 350;
    var MIN_CHARS = 3;

    var CAT_EMOJI = {
        design: '🎨', engineer: '⚡', dev: '⚡', code: '⚡',
        leader: '👥', manage: '👥', 'ai': '🤖', ml: '🤖',
        machine: '🤖', data: '📊', analyt: '📊', market: '📣',
        comply: '🛡', legal: '🛡', compliance: '🛡', finance: '💹',
        sales: '🤝', health: '🏥', security: '🔐',
    };

    function getCatEmoji(name) {
        var lower = (name || '').toLowerCase();
        for (var key in CAT_EMOJI) {
            if (lower.indexOf(key) !== -1) { return CAT_EMOJI[key]; }
        }
        return '📚';
    }

    function CwSearch(instanceid) {
        this.instanceid = instanceid;
        this.wrap       = document.querySelector('[data-instanceid="' + instanceid + '"]');
        this.input      = this.wrap && this.wrap.querySelector('[data-action="cw-search"]');
        this.resultsBox = this.wrap && this.wrap.querySelector('[data-region="cw-results"]');
        this.catid      = 0;
        this._timer     = null;
    }

    /**
     * Apply the block's bgcolor (dark theme) to the Moodle .card wrapper.
     * For the light theme, data-bgcolor is absent so this is a no-op.
     */
    CwSearch.prototype.applyBgColor = function() {
        if (!this.wrap) { return; }
        var bgcolor = this.wrap.dataset.bgcolor;
        if (!bgcolor) { return; }
        var card = this.wrap.closest('.card');
        if (card) {
            card.style.background = bgcolor;
        } else {
            this.wrap.style.background = bgcolor;
        }
    };

    CwSearch.prototype.portalInit = function() {
        if (!this.resultsBox) { return; }
        document.body.appendChild(this.resultsBox);
        this.resultsBox.style.position = 'absolute';
        this.resultsBox.style.zIndex   = '99999';
    };

    /**
     * Position the portalled results dropdown below the search row.
     * Works for both dark (cw2-search-row) and light (cwl-search-row) templates
     * by targeting the shared data-region="cw-search-row" attribute.
     */
    CwSearch.prototype.positionResults = function() {
        var anchor = this.wrap && this.wrap.querySelector('[data-region="cw-search-row"]');
        if (!anchor || !this.resultsBox) { return; }
        var r       = anchor.getBoundingClientRect();
        var scrollY = window.pageYOffset || document.documentElement.scrollTop;
        var scrollX = window.pageXOffset || document.documentElement.scrollLeft;
        this.resultsBox.style.top   = (r.bottom + scrollY + 6) + 'px';
        this.resultsBox.style.left  = (r.left  + scrollX) + 'px';
        this.resultsBox.style.width = r.width + 'px';
    };

    CwSearch.prototype.init = function() {
        if (!this.wrap) { return; }
        var self = this;

        self.portalInit();
        self.applyBgColor();

        // Emoji pills (works for both cw2-pill and cwl-pill via shared data-action)
        self.wrap.querySelectorAll('[data-action="cw-cat"][data-catname]').forEach(function(pill) {
            pill.textContent = getCatEmoji(pill.dataset.catname || '') + ' ' + pill.dataset.catname;
        });

        // Typing debounce
        self.input.addEventListener('input', function() {
            clearTimeout(self._timer);
            var q = this.value.trim();
            if (q.length < MIN_CHARS) { self.hideResults(); return; }
            self._timer = setTimeout(function() { self.search(self.input.value.trim()); }, DELAY_MS);
        });

        // Find button
        var btn = self.wrap.querySelector('[data-action="cw-search-btn"]');
        if (btn) {
            btn.addEventListener('click', function() {
                var q = self.input.value.trim();
                if (q.length >= MIN_CHARS) { self.search(q); }
            });
        }

        // Category pills — removes both dark and light active classes
        var pills = self.wrap.querySelectorAll('[data-action="cw-cat"]');
        pills.forEach(function(pill) {
            pill.addEventListener('click', function() {
                pills.forEach(function(p) {
                    p.classList.remove('active', 'cw2-pill--active', 'cwl-pill--active');
                });
                this.classList.add('active');
                self.catid = parseInt(this.dataset.catid, 10) || 0;
                var q = self.input.value.trim();
                if (q.length >= MIN_CHARS || self.catid > 0) { self.search(q); }
            });
        });

        // Close on outside click
        document.addEventListener('click', function(e) {
            if (!self.wrap.contains(e.target) && !self.resultsBox.contains(e.target)) {
                self.hideResults();
            }
        });
    };

    CwSearch.prototype.search = function(query) {
        var self = this;
        Ajax.call([{
            methodname: 'block_corpwelcome_search_courses',
            args: { query: query, categoryid: self.catid, limit: 8 },
            done: function(data) {
                Templates.render('block_corpwelcome/search_results', data).then(function(html) {
                    self.resultsBox.innerHTML = html;
                    self.resultsBox.removeAttribute('hidden');
                    self.positionResults();
                    return null;
                }).catch(Notification.exception);
            },
            fail: Notification.exception
        }]);
    };

    CwSearch.prototype.hideResults = function() {
        if (this.resultsBox) {
            this.resultsBox.innerHTML = '';
            this.resultsBox.setAttribute('hidden', '');
        }
    };

    return {
        init: function(opts) {
            var cws = new CwSearch(opts.instanceid);
            cws.init();
        }
    };
});
