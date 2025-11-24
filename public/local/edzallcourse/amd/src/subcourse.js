define(["jquery", "core/notification"], function ($, Notification) {

    const state = {
        ajaxUrl: M.cfg.wwwroot + "/local/edzallcourse/subcat_ajax.php"
    };

    function fetchCourses(subcatId) {
        const $results = $("#courselist-results");

        if (!subcatId) {
            $results.empty();
            return;
        }

        $.ajax({
            url: state.ajaxUrl,
            method: "POST",
            data: {
                subcategoryid: subcatId,
                sesskey: M.cfg.sesskey
            },
            dataType: "json"
        })
          .done(function (resp) {
            if (resp.html) {
                $("#courselist-results").html(resp.html);
            } else {
                $("#courselist-results").html("<p>No courses found.</p>");
            }

            // Append category description
            if (resp.description) {
                $(".subcatname").html(resp.categoryname);
                $(".subcatinfo").html(resp.description);
            } else {
                 $(".subcatname").empty();
                $(".subcatinfo").empty();
            }
        })

        .fail(function (jqXHR, textStatus, errorThrown) {
            Notification.exception(new Error("Failed to fetch courses: " + errorThrown));
        });
    }

    function bindSubcategorySelect() {
        $(document).on("change", "#subcategory", function () {
            const subcatId = $(this).val();
            fetchCourses(subcatId);
        });
    }

    function init() {
        bindSubcategorySelect();
    }

    return {
        init: init,
        fetchCourses: fetchCourses
    };
});
