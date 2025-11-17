define(['jquery', 'core/ajax', 'core/chartjs'], function($, Ajax, Chart) {

    let siteUsageChart;

    // =========================
    // Render Usage Chart
    // =========================
    function renderSiteUsageChart(resp) {
        if (siteUsageChart) {
            siteUsageChart.destroy();
        }

        if (resp.usage24h) {
            let ctx = document.getElementById('usageChart');
            if (ctx) {
                siteUsageChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: resp.usage24h.labels,
                        datasets: [
                            {
                                label: 'CPU %',
                                data: resp.usage24h.cpu,
                                fill: false,
                                borderColor: 'rgba(75, 192, 192, 1)',
                                tension: 0.1
                            },
                            {
                                label: 'Memory %',
                                data: resp.usage24h.memory,
                                fill: false,
                                borderColor: 'rgba(255, 159, 64, 1)',
                                tension: 0.1
                            },
                            {
                                label: 'Storage %',
                                data: resp.usage24h.storage,
                                fill: false,
                                borderColor: 'rgba(153, 102, 255, 1)',
                                tension: 0.1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'top' }
                        },
                        scales: {
                            y: { beginAtZero: true, max: 100 }
                        }
                    }
                });
            }
        }
    }

    return {
        init: function() {

            function loadSiteData(range) {
                Ajax.call([{
                    methodname: 'local_edzdashboardview_get_site_dashboard_data',
                    args: { range: range }
                }])[0].done(function(resp) {

                    console.log("✅ SITE Response:", resp);

                    

                    // =========================
                    // CPU, Memory, Storage
                    // =========================
                    if (resp.cpu) {
                        $('.status-item strong:contains("CPU")')
                            .closest('.status-item')
                            .find('.progress-bar')
                            .css('width', resp.cpu.percent + '%')
                            .text(resp.cpu.percent.toFixed(2) + '%');
                    }

                    if (resp.memory) {
                        $('.status-item strong:contains("Memory")')
                            .closest('.status-item')
                            .find('.progress-bar')
                            .css('width', resp.memory.percent + '%')
                            .text(resp.memory.percent.toFixed(2) + '%');
                    }

                    if (resp.storage) {
                        $('.status-item strong:contains("Storage")')
                            .closest('.status-item')
                            .find('.progress-bar')
                            .css('width', resp.storage.percent + '%')
                            .text(resp.storage.percent.toFixed(2) + '%');
                    }

                    // =========================
                    // Live Users
                    // =========================
                    $('.status-item span.badge.bg-danger').text(
                        "Live users (last 30 min) " + (resp.liveusers ?? 0)
                    );

                    // =========================
                    // Site Context Info
                    // =========================
                    if (resp.sitecontext) {
                        if (resp.sitecontext.theme_designermode) {
                            $('#theme-mode').text('Enabled')
                                .removeClass('bg-danger').addClass('bg-success');
                        } else {
                            $('#theme-mode').text('Disabled')
                                .removeClass('bg-success').addClass('bg-danger');
                        }

                        if (resp.sitecontext.debugging) {
                            $('#debug-mode').text('Enabled')
                                .removeClass('bg-danger').addClass('bg-success');
                        } else {
                            $('#debug-mode').text('Disabled')
                                .removeClass('bg-success').addClass('bg-danger');
                        }

                        $('#plugin-count').text(resp.sitecontext.plugins ?? 0);
                        $('#srcdir-size').text((resp.sitecontext.srcdirsize ?? '0MB') + ' Used');
                        $('#datadir-size').text((resp.sitecontext.datadirsize ?? '0MB') + ' Used');
                    }

                    // =========================
                    // Call Usage Chart Renderer
                    // =========================
                    renderSiteUsageChart(resp);

                }).fail(function(err) {
                    console.error("❌ Error loading site data:", err);
                });
            }

            // ✅ Initial load
            loadSiteData('1day');

            // ✅ Range switcher
            $(document).on('change', 'input[name="range-site"]', function() {
                loadSiteData($(this).val());
            });
        }
    };
});
