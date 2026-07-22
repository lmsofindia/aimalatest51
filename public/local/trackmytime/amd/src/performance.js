// Performance page interactivity:
//   1. Chart period dropdown (weekly / monthly / quarterly).
//   2. Lightweight client-side pagination for any [data-tmt-paginate] list
//      (grades table, badges grid, recent activity) so large sets stay tidy.
// No external chart library — bars/donut are server-rendered CSS.
define([], function() {
    'use strict';

    function chartDropdown() {
        var sel = document.querySelector('[data-tmt-chart-select]');
        if (!sel) { return; }
        var charts = document.querySelectorAll('[data-tmt-chart]');
        var apply = function() {
            charts.forEach(function(c) {
                c.hidden = (c.getAttribute('data-tmt-chart') !== sel.value);
            });
        };
        sel.addEventListener('change', apply);
        apply();
    }

    function paginate(container) {
        var size  = parseInt(container.getAttribute('data-tmt-paginate'), 10) || 8;
        var items = Array.prototype.slice.call(container.children);
        if (items.length <= size) { return; }
        var pages = Math.ceil(items.length / size);
        var page  = 0;

        var host  = container.closest('table') || container;
        var pager = document.createElement('div');
        pager.className = 'tmt-pager';
        var prev  = document.createElement('button');
        prev.type = 'button'; prev.className = 'tmt-pager__btn'; prev.setAttribute('aria-label', 'Previous'); prev.innerHTML = '&lsaquo;';
        var next  = document.createElement('button');
        next.type = 'button'; next.className = 'tmt-pager__btn'; next.setAttribute('aria-label', 'Next'); next.innerHTML = '&rsaquo;';
        var label = document.createElement('span');
        label.className = 'tmt-pager__label';
        pager.appendChild(prev); pager.appendChild(label); pager.appendChild(next);
        host.parentNode.insertBefore(pager, host.nextSibling);

        var render = function() {
            items.forEach(function(el, i) {
                el.style.display = (i >= page * size && i < (page + 1) * size) ? '' : 'none';
            });
            label.textContent = (page + 1) + ' / ' + pages;
            prev.disabled = (page === 0);
            next.disabled = (page === pages - 1);
        };
        prev.addEventListener('click', function() { if (page > 0) { page--; render(); } });
        next.addEventListener('click', function() { if (page < pages - 1) { page++; render(); } });
        render();
    }

    return {
        init: function() {
            chartDropdown();
            document.querySelectorAll('[data-tmt-paginate]').forEach(paginate);
        }
    };
});
