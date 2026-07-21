// AMD module: local_edzallcourse/ui
define(["jquery", "core/notification", "local_edzallcourse/subcourse"], 
function ($, Notification, SubCourse) {

  const state = {
    filters: {},
    ajaxUrl: M.cfg.wwwroot + "/local/edzallcourse/ajax.php",
  };

  function collectFilters() {
    const filters = {};
    $("input.pc-filter-field[type=checkbox]:checked").each(function () {
      const field = $(this).data("field");
      const val = $(this).val();
      if (!field) return;
      filters[field] = val; 
    });
    state.filters = filters;
  }

  function fetchCatTree() {
    collectFilters();
    const payload = {
      filters: JSON.stringify(state.filters),
      sesskey: M.cfg.sesskey,
    };

    $.ajax({
      url: state.ajaxUrl,
      method: "POST",
      data: payload,
      dataType: "json",
    })
    .done(function (resp) {
      if (resp.template) {
        $("#pcattree-results").html(resp.template);

        // 🔹 Auto-load courses for selected subcategory
        const subcatId = $("#subcategory").val();
        if (subcatId) {
          SubCourse.fetchCourses(subcatId); // call subcategory AJAX
        }

      } else {
        $("#pcattree-results").html("<p></p>");
      }
    })
    .fail(function (err) {
      Notification.exception(err);
    });
  }
function bindFilters() {
    $(document).on("change", "input.pc-filter-field[type=checkbox]", function () {

        const field = $(this).data("field");
        const $group = $(`input.pc-filter-field[data-field="${field}"]`);

        // --- CASE 1: User tries to uncheck the ONLY checked checkbox
        if (!this.checked) {
            const anyChecked = $group.filter(":checked").length;

            if (!anyChecked) {
                // Re-check FIRST checkbox (default)
                $group.first().prop("checked", true);
                fetchCatTree();
                return;
            }
        }

        // --- CASE 2: User checks a new checkbox
        if (this.checked) {
            // Uncheck all others
            $group.not(this).prop("checked", false);
        }

        fetchCatTree();
    });
}



  function selectDefaultCheckboxes() {
    const fields = new Set();
    $("input.pc-filter-field[type=checkbox]").each(function () {
      fields.add($(this).data("field"));
    });

    fields.forEach(function (field) {
      const checked = $(`input.pc-filter-field[data-field="${field}"]:checked`);
      if (!checked.length) {
        $(`input.pc-filter-field[data-field="${field}"]`).first().prop("checked", true);
      }
    });
  }

  function init() {
    bindFilters();
    selectDefaultCheckboxes();
    fetchCatTree();
  }

  return {
    init: init
  };
});
