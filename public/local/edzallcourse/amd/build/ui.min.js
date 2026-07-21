// AMD module: local_edzallcourse/ui
define(["jquery", "core/notification"], function ($, Notification) {
  const state = {
    q: "",
    filters: {},
    sort: "popular",
    ajaxUrl: M.cfg.wwwroot + "/local/edzallcourse/ajax.php",
    offset: 0,
    limit: 4,
    loading: false,
    hasMore: false,
  };

  // Collect multiple checkbox values per field
  function collectFilters() {
    const filters = {};
    $("input.lc-filter-field[type=checkbox]:checked").each(function () {
      const field = $(this).data("field");
      const val = $(this).val();
      if (!field) {
        return;
      }
      if (!filters[field]) {
        filters[field] = [];
      }
      filters[field].push(val);
    });
    state.filters = filters;
  }

  // Utility to toggle Load more button
  function updateLoadMoreButton() {
    if (state.hasMore) {
      $("#lc-loadmore").removeClass("d-none");
    } else {
      $("#lc-loadmore").addClass("d-none");
    }
  }

  // Core AJAX fetch for reset (replace) or load (append)
  function fetchCourses(opts) {
    // opts: { reset: boolean } — if reset true replace grid; otherwise append
    opts = opts || {};
    if (state.loading) {
      return;
    }
    state.loading = true;

    if (opts.reset) {
      state.offset = 0;
      state.hasMore = false;
    }

    collectFilters();

    const payload = {
      q: state.q || "",
      filters: Object.keys(state.filters).length
        ? JSON.stringify(state.filters)
        : "{}",
      offset: state.offset,
      sort: state.sort,
      sesskey: M.cfg.sesskey,
    };

    $("#lc-results").addClass("opacity-50");

    return $.ajax({
      url: state.ajaxUrl,
      method: "POST",
      data: payload,
      dataType: "json",
    })
      .done(function (resp) {
        if (!resp || typeof resp.html === "undefined") {
          return;
        }

        const $items = $(resp.html); // parse incoming items
        const newCards =
          $items.filter(".lc-coursecard").length ||
          $items.find(".lc-coursecard").length;

        if (opts.reset) {
          // replace grid contents
          $("#lc-coursegrid").empty().append($items);
          state.offset = newCards;
        } else {
          // append new items
          $("#lc-coursegrid").append($items);
          state.offset += newCards;
        }

        state.hasMore = !!resp.hasmore;
        updateLoadMoreButton();
      })
      .fail(function (err) {
        Notification.exception(err);
      })
      .always(function () {
        $("#lc-results").removeClass("opacity-50");
        state.loading = false;
      });
  }

  // Bind search (debounced) — resets results
  function bindSearch() {
    let timer = null;
    $(document).on("input", "#lc-search", function () {
      state.q = ($(this).val() || "").trim();
      window.clearTimeout(timer);
      timer = window.setTimeout(function () {
        fetchCourses({ reset: true });
      }, 300);
    });
  }
  // sorting 
  function bindSort() {
    $(document).on("change", "#lc-sort", function () {
      state.sort = $(this).val();
      fetchCourses({ reset: true }); 
    });
}


  // Bind filter checkbox changes (reset)
  function bindFilters() {
    $(document).on(
      "change",
      "input.lc-filter-field[type=checkbox]",
      function () {
        fetchCourses({ reset: true });
      }
    );
  }

  // Load more click
  function bindLoadMore() {
    $(document).on("click", "#lc-loadmore", function (e) {
      e.preventDefault();
      if (!state.hasMore || state.loading) {
        return;
      }
      fetchCourses({ reset: false });
    });
  }

  // Reset button clears filters and reloads
  function bindResetFilters() {
    $(document).on("click", "#lc-resetfilters", function (e) {
      e.preventDefault();
      $("input.lc-filter-field[type=checkbox]").prop("checked", false);
      $("#lc-search").val("");
      state.q = "";
      state.filters = {};
      fetchCourses({ reset: true });
    });
  }

  // Toggle all filter groups (existing function - leave as you had it)
  function initToggleAll() {
    const BS = window.bootstrap || null;
    let allExpanded = (function () {
      const total = $("#lc-filters .collapse").length;
      const open = $("#lc-filters .collapse.show").length;
      return total > 0 && total === open;
    })();

    function setAll(open) {
      $("#lc-filters .collapse").each(function () {
        if (BS && BS.Collapse) {
          const inst =
            typeof BS.Collapse.getOrCreateInstance === "function"
              ? BS.Collapse.getOrCreateInstance(this, { toggle: false })
              : new BS.Collapse(this, { toggle: false });
          open ? inst.show() : inst.hide();
        } else {
          $(this).toggleClass("show", open);
        }
      });
      $("#lc-toggleall").text(open ? "Collapse All" : "Expand All");
      allExpanded = open;
    }

    $("#lc-toggleall").text(allExpanded ? "Collapse All" : "Expand All");

    $(document).on("click", "#lc-toggleall", function (e) {
      e.preventDefault();
      setAll(!allExpanded);
    });
  }

  // Init bindings and initial state (call this from mod.init())
  function initBindings() {
    // compute initial offset from server-rendered items on page load
    const initialCount = $("#lc-coursegrid").children().length || 0;
    state.offset = initialCount;

    // check if the load-more button exists and is visible initially
    state.hasMore = $("#lc-loadmore").length
      ? !$("#lc-loadmore").hasClass("d-none")
      : false;
    updateLoadMoreButton();

    bindSearch();
    bindFilters();
    bindResetFilters();
    bindLoadMore();
    bindSort();
    initToggleAll();
  }

  function init() {
    initBindings();
    // note: we do NOT auto-fetch here because initial HTML already rendered.
  }

  return { init };
});
