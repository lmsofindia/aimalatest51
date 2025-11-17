define(["jquery", "core/chartjs"], function ($, Chart) {
  "use strict";

  var chartInstances = { pie: null, bar: null, line: null };

  function safeGetData() {
    return (
      window.edz_workplace_chart_data || {
        levels: {},
        totals: { enrolled: 0, completed: 0, badges: 0, certificates: 0 },
        timeseries: { labels: [], enrolled: [], completed: [] },
        timeseries_badges: { labels: [], badges: [] },
        timeseries_certs: { labels: [], certs: [] },
      }
    );
  }

  function destroyIfExists(inst) {
    if (inst) {
      try {
        inst.destroy();
      } catch (e) {
        console.warn("chart destroy failed", e);
      }
    }
  }

  var colors = {
    blue: { border: "rgba(13,110,253,1)", fill: "rgba(13,110,253,0.10)" },
    green: { border: "rgba(25,135,84,1)", fill: "rgba(25,135,84,0.10)" },
    orange: { border: "rgba(255,159,64,1)", fill: "rgba(255,159,64,0.10)" },
    red: { border: "rgba(220,53,69,1)", fill: "rgba(220,53,69,0.10)" },
    purple: { border: "rgba(111,66,193,1)", fill: "rgba(111,66,193,0.10)" },
  };

  // PIE
  function renderPieLevels(data) {
    var ctxEl = document.getElementById("edz_pie_levels");
    if (!ctxEl) return;
    var ctx = ctxEl.getContext("2d");

    var labels = [],
      values = [];
    for (var i = 1; i <= 5; i++) {
      labels.push("Level " + i);
      values.push(data.levels[i] || 0);
    }
    labels.push("All");
    values.push(data.levels["all"] || 0);

    var bg = [
      colors.blue.fill,
      colors.green.fill,
      colors.orange.fill,
      colors.purple.fill,
      colors.red.fill,
      "rgba(99,102,241,0.10)",
    ];
    var bd = [
      colors.blue.border,
      colors.green.border,
      colors.orange.border,
      colors.purple.border,
      colors.red.border,
      "rgba(79,70,229,1)",
    ];

    destroyIfExists(chartInstances.pie);
    chartInstances.pie = new Chart(ctx, {
      type: "pie",
      data: {
        labels: labels,
        datasets: [
          {
            data: values,
            backgroundColor: bg,
            borderColor: bd,
            borderWidth: 1.5,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: "bottom", labels: { boxWidth: 12, padding: 12,font: { weight: "bold",size: 16} } },
          tooltip: { 
            mode: "nearest", 
            intersect: false,
            titleFont: { weight: "bold", size: 16 },
            bodyFont: { weight: "bold", size: 14 }
        },
        },
      },
    });
  }

  // BAR - compute enrolled/completed from timeseries per selected range; badges/certs left as totals
  function renderBarTotals(data, rangeKey) {
    var ctxEl = document.getElementById("edz_bar_totals");
    if (!ctxEl) return;
    var ctx = ctxEl.getContext("2d");

    var computed = computeEnrolledCompletedFromTimeseries(data, rangeKey);
    var badges = data.totals.badges || 0;
    var certs = data.totals.certificates || 0;

    var labels = ["Enrolled", "Completed", "Badges", "Certificates"];
    var values = [
      computed.enrolled || 0,
      computed.completed || 0,
      badges,
      certs,
    ];

    var bg = [
      colors.blue.fill,
      colors.green.fill,
      colors.orange.fill,
      colors.red.fill,
    ];
    var bd = [
      colors.blue.border,
      colors.green.border,
      colors.orange.border,
      colors.red.border,
    ];

    destroyIfExists(chartInstances.bar);
    chartInstances.bar = new Chart(ctx, {
      type: "bar",
      data: {
        labels: labels,
        datasets: [
          {
            label: "Counts",
            data: values,
            backgroundColor: bg,
            borderColor: bd,
            borderWidth: 1.5,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
        x: { grid: { display: false }, ticks: { font: { weight: "bold", size: 16 } } },
        y: { beginAtZero: true, grid: { color: "rgba(200,200,200,0.06)" }, ticks: { font: { weight: "bold", size: 16 } } },
        },
        plugins: { 
            legend: { display: false }, 
            tooltip: { 
                yAlign: "bottom",
                titleFont: { weight: "bold", size: 16 },
                bodyFont: { weight: "bold", size: 14 }
            } 
        },

      },
    });
  }

  function parseDateLabel(label) {
    var d = new Date(label);
    if (isNaN(d.getTime())) {
      var parts = String(label).match(/(\d{4})-(\d{2})-(\d{2})/);
      if (parts) {
        d = new Date(Number(parts[1]), Number(parts[2]) - 1, Number(parts[3]));
      } else {
        return null;
      }
    }
    return d;
  }

  function computeEnrolledCompletedFromTimeseries(data, rangeKey) {
    var labels = data.timeseries.labels || [];
    var en = data.timeseries.enrolled || [];
    var comp = data.timeseries.completed || [];

    if (!labels.length) {
      return { enrolled: 0, completed: 0 };
    }

    if (rangeKey === "week") {
      // sum last 7 days if available, else sum all
      var n = 7;
      var totalEn = 0,
        totalComp = 0;
      if (labels.length > n) {
        for (var i = labels.length - n; i < labels.length; i++) {
          totalEn += Number(en[i] || 0);
          totalComp += Number(comp[i] || 0);
        }
      } else {
        for (var j = 0; j < labels.length; j++) {
          totalEn += Number(en[j] || 0);
          totalComp += Number(comp[j] || 0);
        }
      }
      return { enrolled: totalEn, completed: totalComp };
    } else if (rangeKey === "month" || rangeKey === "usefilters") {
      var n = 30;
      var tE = 0,
        tC = 0;
      if (labels.length > n) {
        for (var k = labels.length - n; k < labels.length; k++) {
          tE += Number(en[k] || 0);
          tC += Number(comp[k] || 0);
        }
      } else {
        for (var m = 0; m < labels.length; m++) {
          tE += Number(en[m] || 0);
          tC += Number(comp[m] || 0);
        }
      }
      return { enrolled: tE, completed: tC };
    } else if (rangeKey === "year") {
      var ny = 365;
      var te = 0,
        tc = 0;
      if (labels.length > ny) {
        for (var x = labels.length - ny; x < labels.length; x++) {
          te += Number(en[x] || 0);
          tc += Number(comp[x] || 0);
        }
      } else {
        for (var y = 0; y < labels.length; y++) {
          te += Number(en[y] || 0);
          tc += Number(comp[y] || 0);
        }
      }
      return { enrolled: te, completed: tc };
    } else {
      var totE = 0,
        totC = 0;
      for (var z = 0; z < labels.length; z++) {
        totE += Number(en[z] || 0);
        totC += Number(comp[z] || 0);
      }
      return { enrolled: totE, completed: totC };
    }
  }

  // LINE aggregation: week -> Mon..Sun, month -> Wk1..WkN, year -> Jan..Dec, usefilters -> month-style (weeks)
  function aggregateForWeek(labels, enrolled, completed) {
    var days = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
    var en = [0, 0, 0, 0, 0, 0, 0];
    var comp = [0, 0, 0, 0, 0, 0, 0];
    for (var i = 0; i < labels.length; i++) {
      var d = parseDateLabel(labels[i]);
      if (!d) continue;
      var day = d.getDay();
      var idx = day === 0 ? 6 : day - 1;
      en[idx] += Number(enrolled[i] || 0);
      comp[idx] += Number(completed[i] || 0);
    }
    return { labels: days, enrolled: en, completed: comp };
  }

  function aggregateForMonth(labels, enrolled, completed) {
    var buckets = {};
    var maxidx = 0;
    for (var i = 0; i < labels.length; i++) {
      var d = parseDateLabel(labels[i]);
      if (!d) continue;
      var day = d.getDate();
      var idx = Math.floor((day - 1) / 7);
      maxidx = Math.max(maxidx, idx);
      var key = "Wk" + (idx + 1);
      if (!buckets[key]) buckets[key] = { en: 0, comp: 0 };
      buckets[key].en += Number(enrolled[i] || 0);
      buckets[key].comp += Number(completed[i] || 0);
    }
    var outLabels = [],
      outEn = [],
      outComp = [];
    for (var j = 0; j <= maxidx; j++) {
      var k = "Wk" + (j + 1);
      outLabels.push(k);
      outEn.push(buckets[k] ? buckets[k].en : 0);
      outComp.push(buckets[k] ? buckets[k].comp : 0);
    }
    return { labels: outLabels, enrolled: outEn, completed: outComp };
  }

  function aggregateForYear(labels, enrolled, completed) {
    var monthNames = [
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "May",
      "Jun",
      "Jul",
      "Aug",
      "Sep",
      "Oct",
      "Nov",
      "Dec",
    ];
    var en = new Array(12).fill(0),
      comp = new Array(12).fill(0);
    for (var i = 0; i < labels.length; i++) {
      var d = parseDateLabel(labels[i]);
      if (!d) continue;
      var m = d.getMonth();
      en[m] += Number(enrolled[i] || 0);
      comp[m] += Number(completed[i] || 0);
    }
    return { labels: monthNames, enrolled: en, completed: comp };
  }

  function prepareLineDataByRange(originalData, rangeKey) {
    var labels = originalData.timeseries.labels || [];
    var en = originalData.timeseries.enrolled || [];
    var comp = originalData.timeseries.completed || [];

    if (rangeKey === "week") return aggregateForWeek(labels, en, comp);
    if (rangeKey === "month" || rangeKey === "usefilters")
      return aggregateForMonth(labels, en, comp);
    if (rangeKey === "year") return aggregateForYear(labels, en, comp);
    return { labels: labels, enrolled: en, completed: comp };
  }

  function renderLineTimeseriesPrepared(prepdata) {
    var ctxEl = document.getElementById("edz_line_ts");
    if (!ctxEl) return;
    var ctx = ctxEl.getContext("2d");

    destroyIfExists(chartInstances.line);
    chartInstances.line = new Chart(ctx, {
      type: "line",
      data: {
        labels: prepdata.labels,
        datasets: [
          {
            label: "Enrolled",
            data: prepdata.enrolled,
            borderColor: colors.blue.border,
            backgroundColor: colors.blue.fill,
            tension: 0.25,
            pointRadius: 3,
            fill: true,
          },
          {
            label: "Completed",
            data: prepdata.completed,
            borderColor: colors.green.border,
            backgroundColor: colors.green.fill,
            tension: 0.25,
            pointRadius: 3,
            fill: true,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
        x: { ticks: { font: { weight: "bold", size: 16 } } },
        y: { beginAtZero: true, ticks: { font: { weight: "bold", size: 16 } }, grid: { color: "rgba(200,200,200,0.06)" } },
        },
        plugins: {
            legend: { position: "top", labels: { font: { weight: "bold", size: 16 } } },
            tooltip: { 
                mode: "index", 
                intersect: false,
                titleFont: { weight: "bold", size: 16 },
                bodyFont: { weight: "bold", size: 14 }
            },
        },

      },
    });
  }

  function renderAllImmediate() {
    var data = safeGetData();
    renderPieLevels(data);
    var rangeKey = $("#edz_chart_range").val() || "month";
    renderBarTotals(data, rangeKey);
    var prep = prepareLineDataByRange(data, rangeKey);
    renderLineTimeseriesPrepared(prep);
  }

  function init() {
    $(document).ready(function () {
      if (typeof Chart === "undefined") {
        console.error("Moodle core/chartjs did not provide Chart.");
        return;
      }
      renderAllImmediate();

      var resizeTimeout = null;
      $(window).on("resize", function () {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function () {
          try {
            if (chartInstances.pie) chartInstances.pie.resize();
            if (chartInstances.bar) chartInstances.bar.resize();
            if (chartInstances.line) chartInstances.line.resize();
          } catch (e) {
            console.warn("chart resize failed", e);
          }
        }, 150);
      });

      $(document).on("change", "#edz_chart_range", function () {
        var data = safeGetData();
        var rangeKey = $(this).val() || "month";
        renderBarTotals(data, rangeKey);
        var prep = prepareLineDataByRange(data, rangeKey);
        renderLineTimeseriesPrepared(prep);
      });
    });
  }

  return { init: init, _reRender: renderAllImmediate };
});
