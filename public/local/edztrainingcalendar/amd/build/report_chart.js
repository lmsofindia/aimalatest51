define(["jquery", "core/chartjs"], function ($, Chart) {
  "use strict";

  const Dashboard = {
    init: function (pieData) {
      console.log("Dashboard init with", pieData);

      // PIE CHART
      const ctx1 = document.getElementById("edztrainingchart");
      if (ctx1) {
        new Chart(ctx1, {
          type: "pie",
          data: {
            labels: ["Allocated", "In Progress", "Completed"],
            datasets: [
              {
                data: [
                  pieData.allocated,
                  pieData.inprogress,
                  pieData.completed,
                ],
                backgroundColor: ["#6ea8fe", "#ffc107", "#71dd8a"], // lighter modern tones
              },
            ],
          },
          options: {
            responsive: true,
            animation: { duration: 1200, easing: "easeOutQuart" },
            plugins: { legend: { position: "bottom" } },
          },
        });
      }

      // BAR CHART
      const ctx2 = document.getElementById("edztrainingbarchart");
      let barChart = null;
      const renderBar = (label) => {
        const d = pieData; // placeholder for now, can be extended with AJAX
        const data = [d.allocated, d.inprogress, d.completed, d.notstarted];
        if (barChart) barChart.destroy();
        barChart = new Chart(ctx2, {
          type: "bar",
          data: {
            labels: ["Allocated", "In Progress", "Completed", "Not Started"],
            datasets: [
              {
                label: `Stats - ${label}`,
                data,
                backgroundColor: ["#6ea8fe", "#ffc107", "#71dd8a", "#ced4da"],
                hoverBackgroundColor: [
                  "#5c9cff",
                  "#f5b301",
                  "#5dc176",
                  "#adb5bd",
                ],
              },
            ],
          },
          options: { responsive: true, scales: { y: { beginAtZero: true } } },
        });
      };
      renderBar("This Week");
      $("#barFilter").on("change", function () {
        renderBar($(this).find("option:selected").text());
      });

      // Search + Pagination
      const rowsPerPage = 10;
      let currentPage = 1;
      const $rows = $("#edz-report-table tbody tr");

      function renderTable() {
        const term = $("#edz-search").val().toLowerCase();
        const visible = [];
        $rows.each(function () {
          const text = $(this).text().toLowerCase();
          if (text.indexOf(term) !== -1) {
            visible.push(this);
          }
        });
        const total = visible.length;
        const pages = Math.ceil(total / rowsPerPage) || 1;
        if (currentPage > pages) currentPage = pages;

        $rows.hide();
        visible
          .slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage)
          .forEach((tr) => $(tr).show());

        renderPagination(pages, total);
      }

      function renderPagination(pages, total) {
        const $p = $("#edz-pagination");
        $p.empty();
        if (pages <= 1) {
          $p.text(`Showing ${total} result(s)`);
          return;
        }
        const $ul = $("<ul class='pagination'></ul>");
        for (let i = 1; i <= pages; i++) {
          const active = i === currentPage ? "active" : "";
          $ul.append(
            `<li class='page-item ${active}'><a class='page-link' href='#' data-page='${i}'>${i}</a></li>`
          );
        }
        $p.append($ul);
        $p.find("a").on("click", function (e) {
          e.preventDefault();
          currentPage = parseInt($(this).data("page"));
          renderTable();
        });
      }

      $("#edz-search").on("input", function () {
        currentPage = 1;
        renderTable();
      });

      renderTable();
    },
  };

  return Dashboard;
});
