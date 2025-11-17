/**
 * @module     local_edztrainingcalendar/report_chart
 * @package    local_edztrainingcalendar
 * @copyright  ...
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["jquery", "core/chartjs"], function ($, Chart) {
  "use strict";

  /**
   * Initialise chart.
   * @param {Object} data - {allocated: int, inprogress: int, completed: int}
   */
  function init(data) {
    const ctx = document.getElementById("edztrainingchart");
    if (!ctx) {
      console.warn("Chart canvas not found: #edztrainingchart");
      return;
    }

    // normalise data
    const d = {
      allocated: parseInt(data.allocated || 0, 10),
      inprogress: parseInt(data.inprogress || 0, 10),
      completed: parseInt(data.completed || 0, 10),
    };

    const chartData = {
      labels: ["Allocated", "In Progress", "Completed"],
      datasets: [
        {
          data: [d.allocated, d.inprogress, d.completed],
          backgroundColor: ["#3498db", "#f39c12", "#2ecc71"],
          borderWidth: 1,
        },
      ],
    };

    const options = {
      responsive: true,
      plugins: {
        legend: { position: "bottom" },
        title: { display: true, text: "Training Status Overview" },
      },
    };

    // destroy any existing chart on the same canvas
    if (ctx._edzChart) {
      try {
        ctx._edzChart.destroy();
      } catch (e) {}
    }

    // create the chart using Moodle's built-in Chart.js
    ctx._edzChart = new Chart(ctx, {
      type: "pie",
      data: chartData,
      options: options,
    });
  }

  return { init: init };
});
