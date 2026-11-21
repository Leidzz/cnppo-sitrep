/**
 * CNPPO SITREP — shared Chart.js theme.
 *
 * Load AFTER chart.umd.js. Views call the small factories below and pass
 * only their data; colors, fonts, grid, tooltip and empty-state handling
 * live here so every chart in the system looks the same.
 *
 *   SitrepCharts.line(el, labels, data, { label: 'SITREPs' })
 *   SitrepCharts.bar(el, labels, data, { color: SitrepCharts.colors.green, horizontal: true })
 *   SitrepCharts.doughnut(el, labels, data, { cutout: '62%', statusColors: true, centerLabel: 'Total' })
 *
 * Every factory returns null (instead of throwing) when the canvas is not on
 * the page, e.g. when <x-chart-card :empty="true"> rendered an empty state.
 *
 * Kept as a plain script in public/vendor (not a Vite module) on purpose:
 * Chart.js itself is served locally from here so the system works on a
 * restricted network, and this keeps both files in one place.
 */
(function () {
    if (typeof window.Chart === 'undefined') {
        console.error('[SitrepCharts] chart.umd.js must be loaded before chart-theme.js');
        return;
    }

    const Chart = window.Chart;

    const colors = {
        blue: '#2563EB',
        green: '#16A34A',
        amber: '#D97706',
        red: '#DC2626',
        navy: '#16233F',
        navyMid: '#3E5788',
        violet: '#7C3AED',
        teal: '#0D9488',
        slate: '#64748B',
        grid: '#EEF2F7',
    };

    // Workflow status -> color. Keep in sync with tailwind.config.js `status`
    // tokens and components/status-badge.blade.php.
    const STATUS_COLORS = {
        'Draft': '#64748B',
        'Submitted': '#2563EB',
        'Under Review': '#D97706',
        'Validated': '#16A34A',
        'Returned for Correction': '#DC2626',
    };

    // Categorical palette for non-status data (report types, units).
    // Navy-led so charts sit comfortably next to the brand chrome.
    const palette = ['#213458', '#2563EB', '#0D9488', '#D97706', '#7C3AED', '#94A6C8', '#DC2626'];

    // Light / dark tokens. Everything theme-dependent reads T() at draw
    // time, so a theme switch only needs chart.update() — no reload.
    const LIGHT = {
        grid: '#EEF2F7', tick: '#64748B', strong: '#16233F', card: '#FFFFFF', track: '#EEF2F7',
        bar: '#3E5788', noData: '#94A3B8', palette,
    };
    const DARK = {
        grid: 'rgba(255,255,255,0.06)', tick: '#8B9BB4', strong: '#F1F5FB', card: '#111A2E', track: 'rgba(255,255,255,0.08)',
        bar: '#6079A6', noData: '#64748B',
        palette: ['#94A6C8', '#3B82F6', '#14B8A6', '#F59E0B', '#A78BFA', '#64748B', '#F87171'],
    };
    const isDark = () => document.documentElement.classList.contains('dark');
    const T = () => (isDark() ? DARK : LIGHT);

    // ---- Global defaults ---------------------------------------------------
    Chart.defaults.font.family = '"Public Sans", ui-sans-serif, system-ui, sans-serif';
    Chart.defaults.font.size = 11;
    function applyDefaults() {
        Chart.defaults.color = T().tick;
        Chart.defaults.borderColor = T().grid;
    }
    applyDefaults();
    Chart.defaults.maintainAspectRatio = false;
    Chart.defaults.animation.duration = 600;

    Object.assign(Chart.defaults.plugins.tooltip, {
        backgroundColor: colors.navy,
        titleColor: '#FFFFFF',
        bodyColor: '#E2E8F0',
        padding: 10,
        cornerRadius: 8,
        displayColors: true,
        boxPadding: 4,
    });

    // ---- Plugins -----------------------------------------------------------
    // "No data": centered message instead of an empty canvas when every
    // value is zero or there are no labels at all.
    const noDataPlugin = {
        id: 'sitrepNoData',
        afterDraw(chart, _args, opts) {
            if (chart.options.plugins && chart.options.plugins.sitrepNoData === false) return;
            const hasData = chart.data.datasets.some(
                (ds) => Array.isArray(ds.data) && ds.data.some((v) => Number(v) > 0)
            );
            if (hasData) return;

            const { ctx, chartArea } = chart;
            if (!chartArea) return;

            if (chart.config.type === 'doughnut' || chart.config.type === 'pie') {
                ctx.clearRect(0, 0, chart.width, chart.height);
            }

            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = T().noData;
            ctx.font = `500 12px ${Chart.defaults.font.family}`;
            ctx.fillText(
                (opts && opts.text) || 'No data for this period',
                (chartArea.left + chartArea.right) / 2,
                (chartArea.top + chartArea.bottom) / 2
            );
            ctx.restore();
        },
    };

    // Doughnut center total (only when options.plugins.sitrepCenter.label is set).
    const centerPlugin = {
        id: 'sitrepCenter',
        afterDatasetsDraw(chart, _args, opts) {
            if (!opts || !opts.label || chart.config.type !== 'doughnut') return;
            const total = chart.data.datasets[0].data.reduce((a, b) => a + Number(b || 0), 0);
            if (total === 0) return;
            const meta = chart.getDatasetMeta(0);
            if (!meta.data.length) return;
            const { x, y } = meta.data[0];
            const { ctx } = chart;
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = T().strong;
            ctx.font = `700 22px ${Chart.defaults.font.family}`;
            ctx.fillText(total.toLocaleString(), x, y - 7);
            ctx.fillStyle = T().tick;
            ctx.font = `600 10px ${Chart.defaults.font.family}`;
            ctx.fillText(String(opts.label).toUpperCase(), x, y + 12);
            ctx.restore();
        },
    };

    // Hides the skeleton in <x-chart-card> once the chart has drawn.
    const readyPlugin = {
        id: 'sitrepReady',
        afterRender(chart) {
            const body = chart.canvas.closest('[data-chart-body]');
            if (body && !body.dataset.ready) body.dataset.ready = '1';
        },
    };

    Chart.register(noDataPlugin, centerPlugin, readyPlugin);

    // ---- Helpers -----------------------------------------------------------
    const valueAxis = () => ({
        beginAtZero: true,
        ticks: { precision: 0, color: () => T().tick },
        grid: { color: () => T().grid },
        border: { display: false },
    });
    const categoryAxis = (ticks = {}) => ({
        grid: { display: false },
        ticks: { color: () => T().tick, ...ticks },
        border: { display: false },
    });

    function el(target) {
        return typeof target === 'string' ? document.getElementById(target) : target;
    }

    function hexToRgba(hex, alpha) {
        const n = parseInt(hex.replace('#', ''), 16);
        return `rgba(${(n >> 16) & 255}, ${(n >> 8) & 255}, ${n & 255}, ${alpha})`;
    }

    // Long category labels wrap onto two lines instead of rotating 45°.
    function wrap(label, max = 14) {
        const s = String(label);
        if (s.length <= max) return s;
        const words = s.split(' ');
        const lines = [''];
        words.forEach((w) => {
            const cur = lines[lines.length - 1];
            if ((cur + ' ' + w).trim().length > max && cur) lines.push(w);
            else lines[lines.length - 1] = (cur + ' ' + w).trim();
        });
        return lines;
    }

    // Common PNP abbreviation for axis ticks only (tooltips keep full names).
    function shortUnit(label) {
        return String(label).replace('Provincial Mobile Force Company', 'PMFC');
    }

    function statusColorsFor(labels) {
        return labels.map((l, i) => STATUS_COLORS[l] || palette[i % palette.length]);
    }

    // ---- Factories ---------------------------------------------------------
    function line(target, labels, data, opts = {}) {
        const node = el(target);
        if (!node) return null;
        const color = opts.color || colors.blue;
        return new Chart(node, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: opts.label || 'SITREPs',
                    data,
                    borderColor: color,
                    backgroundColor: hexToRgba(color, 0.08),
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: labels.length > 14 ? 0 : 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: color,
                }],
            },
            options: {
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false } },
                scales: { x: categoryAxis({ maxTicksLimit: 10 }), y: valueAxis() },
            },
        });
    }

    function bar(target, labels, data, opts = {}) {
        const node = el(target);
        if (!node) return null;
        const horizontal = !!opts.horizontal;
        return new Chart(node, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: opts.label || 'SITREPs',
                    data,
                    backgroundColor: opts.colors || opts.color || (() => T().bar),
                    borderRadius: 4,
                    maxBarThickness: horizontal ? 22 : 44,
                }],
            },
            options: {
                indexAxis: horizontal ? 'y' : 'x',
                plugins: { legend: { display: false } },
                scales: horizontal
                    ? { x: valueAxis(), y: categoryAxis({ callback(v) { return shortUnit(this.getLabelForValue(v)); } }) }
                    : { x: categoryAxis({ autoSkip: false, maxRotation: 0, callback(v) { return wrap(this.getLabelForValue(v)); } }), y: valueAxis() },
            },
        });
    }

    function doughnut(target, labels, data, opts = {}) {
        const node = el(target);
        if (!node) return null;
        const bg = opts.colors || (opts.statusColors ? statusColorsFor(labels) : ((ctx) => T().palette[ctx.dataIndex % T().palette.length]));
        return new Chart(node, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{ data, backgroundColor: bg, borderWidth: 2, borderColor: () => T().card, hoverOffset: 4 }],
            },
            options: {
                cutout: opts.cutout || '64%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 8, boxHeight: 8, usePointStyle: true, pointStyle: 'circle', padding: 12 } },
                    sitrepCenter: { label: opts.centerLabel || null },
                },
            },
        });
    }

    // Several series on one line chart, e.g. submitted vs validated.
    // datasets: [{ label, data, color, dashed? }]
    function multiLine(target, labels, datasets) {
        const node = el(target);
        if (!node) return null;
        return new Chart(node, {
            type: 'line',
            data: {
                labels,
                datasets: datasets.map((d, i) => ({
                    label: d.label,
                    data: d.data,
                    borderColor: d.color,
                    backgroundColor: hexToRgba(d.color, i === 0 ? 0.08 : 0),
                    fill: i === 0,
                    tension: 0.35,
                    borderWidth: 2,
                    borderDash: d.dashed ? [5, 4] : [],
                    pointRadius: labels.length > 14 ? 0 : 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: d.color,
                })),
            },
            options: {
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'top', align: 'end', labels: { boxWidth: 8, boxHeight: 8, usePointStyle: true, pointStyle: 'circle' } } },
                scales: { x: categoryAxis({ maxTicksLimit: 10 }), y: valueAxis() },
            },
        });
    }

    // Stacked bars, e.g. pending reports by age bucket and status.
    // datasets: [{ label, data, color }]
    function stackedBar(target, labels, datasets, opts = {}) {
        const node = el(target);
        if (!node) return null;
        const horizontal = opts.horizontal !== false;
        const stackedValue = { ...valueAxis(), stacked: true };
        const stackedCat = { ...categoryAxis(), stacked: true };
        return new Chart(node, {
            type: 'bar',
            data: {
                labels,
                datasets: datasets.map((d) => ({
                    label: d.label, data: d.data, backgroundColor: d.color,
                    borderRadius: 3, maxBarThickness: 26,
                })),
            },
            options: {
                indexAxis: horizontal ? 'y' : 'x',
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'top', align: 'end', labels: { boxWidth: 8, boxHeight: 8, usePointStyle: true, pointStyle: 'circle' } } },
                scales: horizontal ? { x: stackedValue, y: stackedCat } : { x: stackedCat, y: stackedValue },
            },
        });
    }

    // Half-circle gauge for a percentage (0–100). Shows "No decisions yet"
    // when pct is null.
    function gauge(target, pct, opts = {}) {
        const node = el(target);
        if (!node) return null;
        const value = pct === null || pct === undefined ? 0 : Math.max(0, Math.min(100, Number(pct)));
        const color = opts.color || (value >= 80 ? colors.green : value >= 60 ? colors.amber : colors.red);
        const gaugeText = {
            id: 'sitrepGaugeText',
            afterDatasetsDraw(chart) {
                const meta = chart.getDatasetMeta(0);
                if (!meta.data.length) return;
                const { x, y } = meta.data[0];
                const { ctx } = chart;
                ctx.save();
                ctx.textAlign = 'center';
                ctx.fillStyle = T().strong;
                ctx.font = `700 26px ${Chart.defaults.font.family}`;
                ctx.fillText(pct === null || pct === undefined ? '—' : `${value}%`, x, y - 8);
                ctx.fillStyle = T().tick;
                ctx.font = `600 10px ${Chart.defaults.font.family}`;
                ctx.fillText(String(opts.label || '').toUpperCase(), x, y + 10);
                ctx.restore();
            },
        };
        return new Chart(node, {
            type: 'doughnut',
            data: { labels: ['Rate', ''], datasets: [{ data: [value, 100 - value], backgroundColor: (ctx) => (ctx.dataIndex === 0 ? color : T().track), borderWidth: 0 }] },
            options: {
                rotation: -90, circumference: 180, cutout: '74%',
                plugins: { legend: { display: false }, tooltip: { enabled: false }, sitrepNoData: false },
            },
            plugins: [gaugeText],
        });
    }

    // Re-color every chart when the user switches light/dark mode.
    window.addEventListener('theme-changed', () => {
        applyDefaults();
        Object.values(Chart.instances).forEach((c) => c.update('none'));
    });

    window.SitrepCharts = { colors, palette, STATUS_COLORS, line, multiLine, bar, stackedBar, doughnut, gauge };
})();
