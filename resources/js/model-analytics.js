import {
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    Filler,
    Legend,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
} from 'chart.js';

Chart.register(
    BarController,
    BarElement,
    CategoryScale,
    Filler,
    Legend,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
);

const chartInstances = new Map();
const getChartColors = () => {
    const isLight = document.documentElement.classList.contains('theme-light') || document.body?.classList.contains('theme-light');
    return {
        cyan: '#0284c7',
        blue: '#2563eb',
        emerald: '#059669',
        amber: '#d97706',
        rose: '#e11d48',
        slate: '#64748b',
        grid: isLight ? 'rgba(148, 163, 184, 0.25)' : 'rgba(71, 107, 159, 0.25)',
        text: isLight ? '#475569' : '#94a3b8',
    };
};

const formatInt = (num) => Number(num ?? 0).toLocaleString();
const formatDec = (num, digits = 2) => Number(num ?? 0).toFixed(digits);
const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

const baseOptions = (extra = {}, chartColors = getChartColors()) => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 400, easing: 'easeOutQuart' },
    plugins: {
        legend: { labels: { color: chartColors.text, boxWidth: 10, font: { size: 10 } } },
        tooltip: { padding: 10, backgroundColor: '#071738', titleColor: '#fff', bodyColor: '#cbd5e1' },
    },
    scales: {
        x: { grid: { display: false }, ticks: { color: chartColors.text, font: { size: 10 } } },
        y: { beginAtZero: true, grid: { color: chartColors.grid }, ticks: { color: chartColors.text, font: { size: 10 } } },
    },
    ...extra,
});

const createChart = (canvas, config) => {
    if (!canvas) return;
    chartInstances.get(canvas)?.destroy();
    chartInstances.set(canvas, new Chart(canvas, config));
};

const renderCharts = (analytics, payload) => {
    const charts = payload.charts;
    if (!charts) return;

    const chartColors = getChartColors();
    const scoringCanvas = analytics.querySelector('[data-model-chart="scoring"]');
    const distCanvas = analytics.querySelector('[data-model-chart="distribution"]');
    const yieldCanvas = analytics.querySelector('[data-model-chart="yield"]');
    const basinsCanvas = analytics.querySelector('[data-model-chart="basins"]');

    createChart(scoringCanvas, {
        type: 'bar',
        data: {
            labels: charts.scoringByStatus.labels,
            datasets: [
                { label: 'Avg Relevance (P_rel)', data: charts.scoringByStatus.pRel, backgroundColor: chartColors.cyan, borderRadius: 4 },
                { label: 'Avg Spatial (D_clusters)', data: charts.scoringByStatus.dClusters, backgroundColor: chartColors.amber, borderRadius: 4 },
                { label: 'Avg Confidence (CS)', data: charts.scoringByStatus.confidence, backgroundColor: chartColors.emerald, borderRadius: 4 },
            ],
        },
        options: baseOptions({
            scales: {
                x: { grid: { display: false }, ticks: { color: chartColors.text, font: { size: 10 } } },
                y: { beginAtZero: true, max: 1, grid: { color: chartColors.grid }, ticks: { color: chartColors.text, font: { size: 10 } } },
            },
        }, chartColors),
    });

    createChart(distCanvas, {
        type: 'bar',
        data: {
            labels: charts.scoreDistribution.labels,
            datasets: [
                { label: 'Neural Relevance (P_rel)', data: charts.scoreDistribution.pRelCounts, backgroundColor: chartColors.cyan, borderRadius: 4 },
                { label: 'Final Confidence (CS)', data: charts.scoreDistribution.csCounts, backgroundColor: chartColors.emerald, borderRadius: 4 },
            ],
        },
        options: baseOptions({}, chartColors),
    });

    createChart(yieldCanvas, {
        type: 'bar',
        data: {
            labels: charts.yieldTrend.labels,
            datasets: [
                { label: 'Verified Actionable', data: charts.yieldTrend.actionable, backgroundColor: chartColors.emerald, borderRadius: 3 },
                { label: 'Flood Watch', data: charts.yieldTrend.watch, backgroundColor: chartColors.amber, borderRadius: 3 },
                { label: 'Discarded', data: charts.yieldTrend.discarded, backgroundColor: chartColors.rose, borderRadius: 3 },
            ],
        },
        options: baseOptions({
            scales: {
                x: { stacked: true, grid: { display: false }, ticks: { color: chartColors.text, font: { size: 10 } } },
                y: { stacked: true, beginAtZero: true, grid: { color: chartColors.grid }, ticks: { color: chartColors.text, font: { size: 10 } } },
            },
        }, chartColors),
    });

    createChart(basinsCanvas, {
        type: 'bar',
        data: {
            labels: charts.riverBasins.labels,
            datasets: [
                { label: 'Analyzed Posts', data: charts.riverBasins.values, backgroundColor: chartColors.blue, borderRadius: 5, barThickness: 16 },
            ],
        },
        options: baseOptions({
            indexAxis: 'y',
            scales: {
                x: { beginAtZero: true, grid: { color: chartColors.grid }, ticks: { color: chartColors.text, font: { size: 10 } } },
                y: { grid: { display: false }, ticks: { color: chartColors.text, font: { size: 10 } } },
            },
        }, chartColors),
    });
};

const updateDom = (analytics, payload) => {
    const s = payload.summary;
    const setKpi = (name, val) => {
        const el = analytics.querySelector(`[data-analytics-kpi="${name}"]`);
        if (el) el.textContent = val;
    };
    const setSub = (name, val) => {
        const el = analytics.querySelector(`[data-analytics-sub="${name}"]`);
        if (el) el.textContent = val;
    };

    setKpi('analyzedCount', formatInt(s.analyzedCount));
    setSub('analyzedDetail', `${s.analyzedPct}% of ${formatInt(s.totalInWindow)} posts (${formatInt(s.pendingCount)} pending)`);

    setKpi('actionableCount', formatInt(s.actionableCount));
    setSub('actionableDetail', `${s.actionableRate}% yield • Avg CS ${formatDec(s.avgActionableCs)}`);

    setKpi('watchCount', formatInt(s.watchCount));
    setSub('watchDetail', `${s.watchRate}% of analyzed • ${formatInt(s.discardedTotal)} discarded`);

    setKpi('meanDClusters', formatDec(s.meanDClusters));
    setSub('consensusDetail', `${formatInt(s.uniqueClusters)} K-Means++ clusters • Flood Avg P_rel ${formatDec(s.meanFloodPRel)}`);

    Object.entries(payload.hitlAudit ?? {}).forEach(([field, value]) => {
        const el = analytics.querySelector(`[data-hitl-field="${field}"]`);
        if (el) el.textContent = typeof value === 'number' ? formatInt(value) : String(value);
    });

    const outcomeBody = analytics.querySelector('[data-outcome-matrix]');
    if (outcomeBody && Array.isArray(payload.outcomeMatrix)) {
        const dotMap = { emerald: 'bg-emerald-400', amber: 'bg-amber-400', rose: 'bg-rose-400', slate: 'bg-slate-400' };
        outcomeBody.innerHTML = payload.outcomeMatrix.map((row) => `
            <tr>
                <td class="py-2.5 font-semibold"><span class="mr-2 inline-block h-2 w-2 rounded-full ${dotMap[row.tone] || 'bg-slate-400'}"></span>${escapeHtml(row.label)}</td>
                <td class="py-2.5 text-right font-bold">${formatInt(row.count)}</td>
                <td class="py-2.5 text-right text-slate-400">${formatDec(row.sharePct, 1)}%</td>
                <td class="py-2.5 text-right font-mono">${formatDec(row.avgPRel)}</td>
                <td class="py-2.5 text-right font-mono">${formatDec(row.avgDClusters)}</td>
                <td class="py-2.5 text-right font-mono font-bold text-blue-300">${formatDec(row.avgCs)}</td>
            </tr>
        `).join('');
    }

    const filterList = analytics.querySelector('[data-filter-reasons]');
    if (filterList && Array.isArray(payload.filterReasons)) {
        filterList.innerHTML = payload.filterReasons.length
            ? payload.filterReasons.map((reason) => `
                <div class="flex items-center justify-between gap-3 rounded-xl bg-[#0d2b5b] px-3.5 py-2.5">
                    <div class="min-w-0">
                        <p class="truncate text-xs font-bold text-slate-200">${escapeHtml(reason.label)}</p>
                        <p class="mt-0.5 text-[9px] font-semibold uppercase tracking-wider text-blue-300">${escapeHtml(reason.gate)}</p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-xs font-black text-white">${formatInt(reason.count)}</p>
                        <p class="text-[9px] text-slate-400">${formatDec(reason.sharePct, 1)}%</p>
                    </div>
                </div>
            `).join('')
            : '<div class="rounded-xl bg-[#0d2b5b] p-4 text-center text-xs text-slate-400">No gate or discard attributions recorded in this window.</div>';
    }

    const barangayBody = analytics.querySelector('[data-barangay-table]');
    if (barangayBody && Array.isArray(payload.barangays)) {
        barangayBody.innerHTML = payload.barangays.length
            ? payload.barangays.map((row) => `
                <tr>
                    <td class="py-2 font-bold text-blue-300">${escapeHtml(row.barangay)}</td>
                    <td class="py-2 text-right font-bold">${formatInt(row.total)}</td>
                    <td class="py-2 text-right font-semibold text-emerald-400">${formatInt(row.actionable)}</td>
                    <td class="py-2 text-right text-amber-300">${formatInt(row.watch)}</td>
                    <td class="py-2 text-right text-slate-400">${formatDec(row.actionableRate, 1)}%</td>
                    <td class="py-2 text-right font-mono">${formatDec(row.avgPRel)}</td>
                    <td class="py-2 text-right font-mono">${formatDec(row.avgDClusters)}</td>
                    <td class="py-2 text-right font-mono font-bold text-white">${formatDec(row.avgCs)}</td>
                </tr>
            `).join('')
            : '<tr><td colspan="8" class="py-6 text-center text-xs text-slate-400">No localized barangay signals in this window.</td></tr>';
    }
};

const initModelAnalytics = () => {
    const analytics = document.querySelector('[data-model-analytics]');
    if (!analytics) return;

    let windowsData = {};
    try {
        windowsData = JSON.parse(analytics.dataset.analyticsWindows || '{}');
    } catch {
        windowsData = {};
    }

    const windowSelect = analytics.querySelector('[data-model-window]');
    const update = () => {
        const key = windowSelect?.value || 'all';
        const payload = windowsData[key] || windowsData.all;
        if (!payload) return;
        updateDom(analytics, payload);
        renderCharts(analytics, payload);
    };

    windowSelect?.addEventListener('change', update);

    const themeObserver = new MutationObserver(() => {
        const key = windowSelect?.value || 'all';
        const payload = windowsData[key] || windowsData.all;
        if (payload) renderCharts(analytics, payload);
    });
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    update();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initModelAnalytics);
} else {
    initModelAnalytics();
}
