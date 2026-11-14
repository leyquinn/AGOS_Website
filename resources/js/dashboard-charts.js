import {
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    DoughnutController,
    Filler,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';

Chart.register(
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    DoughnutController,
    Filler,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
);

const floodActivityMockData = {
    '7d': {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        rawReports: [12, 45, 120, 85, 30, 15, 8],
        validatedAlerts: [2, 10, 38, 24, 6, 2, 0],
        confidence: { high: 86, lower: 49 },
        barangays: { labels: ['Poblacion', 'Matina', 'Talomo', 'Buhangin', 'Agdao'], values: [38, 31, 24, 19, 14] },
        sources: { labels: ['Citizen App', 'Social Feeds', 'Authority Overrides'], values: [58, 29, 13] },
    },
    '4w': {
        labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
        rawReports: [140, 480, 210, 95],
        validatedAlerts: [45, 160, 62, 20],
        confidence: { high: 302, lower: 121 },
        barangays: { labels: ['Matina', 'Poblacion', 'Talomo', 'Buhangin', 'Agdao'], values: [164, 142, 108, 84, 61] },
        sources: { labels: ['Citizen App', 'Social Feeds', 'Authority Overrides'], values: [51, 34, 15] },
    },
    '12m': {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        rawReports: [320, 150, 90, 210, 650, 890, 1100, 750, 430, 310, 280, 490],
        validatedAlerts: [90, 30, 15, 50, 210, 310, 420, 240, 110, 80, 70, 130],
        confidence: { high: 1745, lower: 1030 },
        barangays: { labels: ['Talomo', 'Matina', 'Poblacion', 'Buhangin', 'Agdao'], values: [462, 418, 385, 341, 297] },
        sources: { labels: ['Citizen App', 'Social Feeds', 'Authority Overrides'], values: [47, 38, 15] },
    },
    ytd: {
        labels: ['2023', '2024', '2025', '2026'],
        rawReports: [4200, 5800, 6100, 2400],
        validatedAlerts: [1200, 1850, 2100, 820],
        confidence: { high: 3960, lower: 1890 },
        barangays: { labels: ['Poblacion', 'Talomo', 'Matina', 'Buhangin', 'Agdao'], values: [920, 804, 731, 665, 583] },
        sources: { labels: ['Citizen App', 'Social Feeds', 'Authority Overrides'], values: [54, 31, 15] },
    },
};

const filterLabels = { '7d': 'Last 7 Days', '4w': 'Last 4 Weeks', '12m': 'Last 12 Months', ytd: 'All Years (Archive)' };
const chartInstances = new Map();
const getThemeColors = () => {
    const isLight = document.documentElement.classList.contains('theme-light') || document.body?.classList.contains('theme-light');
    return {
        cyan: '#0284c7',
        amber: '#d97706',
        red: '#dc2626',
        emerald: '#059669',
        grid: isLight ? 'rgba(148, 163, 184, 0.25)' : 'rgba(71, 107, 159, 0.25)',
        text: isLight ? '#475569' : '#94a3b8',
        surfaceBorder: isLight ? '#ffffff' : '#071738',
    };
};

let activeDatasets = null;

const getLiveDatasets = () => {
    if (activeDatasets) return activeDatasets;
    const dashboard = document.querySelector('[data-flood-dashboard]');
    if (dashboard?.dataset.chartDatasets) {
        try {
            activeDatasets = JSON.parse(dashboard.dataset.chartDatasets);
            return activeDatasets;
        } catch {
            // fallback
        }
    }
    return floodActivityMockData;
};

const getFloodActivityData = (period) => getLiveDatasets()[period] ?? null;

const chartOptions = (stacked = false, colors = getThemeColors()) => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 500, easing: 'easeOutQuart' },
    interaction: { intersect: false, mode: 'index' },
    plugins: {
        legend: { labels: { color: colors.text, boxWidth: 10, font: { size: 10 } } },
        tooltip: { padding: 10, backgroundColor: '#071738', titleColor: '#fff', bodyColor: '#cbd5e1' },
    },
    scales: {
        x: { stacked, grid: { display: false }, ticks: { color: colors.text, font: { size: 10 } } },
        y: { stacked, beginAtZero: true, grid: { color: colors.grid }, ticks: { color: colors.text, font: { size: 10 } } },
    },
});

const createChart = (canvas, config) => {
    chartInstances.get(canvas)?.destroy();
    const chart = new Chart(canvas, config);
    chartInstances.set(canvas, chart);
};

const renderCharts = (period) => {
    const data = getFloodActivityData(period);
    const activityCanvas = document.querySelector('[data-chart="activity"]');
    const confidenceCanvas = document.querySelector('[data-chart="confidence"]');
    const barangaysCanvas = document.querySelector('[data-chart="barangays"]');
    const sourcesCanvas = document.querySelector('[data-chart="sources"]');

    if (!data || !activityCanvas || !confidenceCanvas || !barangaysCanvas || !sourcesCanvas) {
        return;
    }

    const colors = getThemeColors();

    createChart(activityCanvas, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [
                { label: 'Incoming reports', data: data.rawReports, borderColor: colors.cyan, backgroundColor: 'rgba(2,132,199,.15)', fill: true, tension: .35, pointRadius: 3 },
                { label: 'Validated alerts', data: data.validatedAlerts, borderColor: colors.emerald, backgroundColor: 'rgba(5,150,105,.12)', fill: true, tension: .35, pointRadius: 3 },
            ],
        },
        options: chartOptions(false, colors),
    });
    createChart(confidenceCanvas, {
        type: 'bar',
        data: { labels: ['CS ≥ 0.85', 'Lower confidence'], datasets: [{ label: 'Signals', data: [data.confidence.high, data.confidence.lower], backgroundColor: [colors.emerald, colors.amber], borderRadius: 6, maxBarThickness: 54 }] },
        options: chartOptions(false, colors),
    });
    createChart(barangaysCanvas, {
        type: 'bar',
        data: { labels: data.barangays.labels, datasets: [{ label: 'Validated alerts', data: data.barangays.values, backgroundColor: colors.cyan, borderRadius: 5, barThickness: 18 }] },
        options: { ...chartOptions(false, colors), indexAxis: 'y', scales: { x: { beginAtZero: true, grid: { color: colors.grid }, ticks: { color: colors.text, font: { size: 10 } } }, y: { grid: { display: false }, ticks: { color: colors.text, font: { size: 10 } } } } },
    });
    createChart(sourcesCanvas, {
        type: 'doughnut',
        data: { labels: data.sources.labels, datasets: [{ data: data.sources.values, backgroundColor: [colors.cyan, colors.amber, colors.red], borderColor: colors.surfaceBorder, borderWidth: 4 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'bottom', labels: { color: colors.text, boxWidth: 10, padding: 14, font: { size: 10 } } } } },
    });
};

const initDashboardCharts = () => {
    const dashboard = document.querySelector('[data-flood-dashboard]');
    if (!dashboard) return;

    let currentPeriod = '7d';
    const setPeriod = (period) => {
        if (!getLiveDatasets()[period]) return;
        currentPeriod = period;
        dashboard.querySelectorAll('[data-period-label]').forEach((label) => { label.textContent = filterLabels[period]; });
        dashboard.querySelectorAll('[data-chart-filter]').forEach((button) => {
            const isActive = button.dataset.chartFilter === period;
            button.classList.toggle('bg-blue-600', isActive);
            button.classList.toggle('text-white', isActive);
            button.classList.toggle('text-slate-400', !isActive);
            button.setAttribute('aria-pressed', String(isActive));
        });
        renderCharts(period);
    };

    dashboard.querySelectorAll('[data-chart-filter]').forEach((button) => button.addEventListener('click', () => setPeriod(button.dataset.chartFilter)));

    const themeObserver = new MutationObserver(() => renderCharts(currentPeriod));
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    setPeriod('7d');
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboardCharts);
} else {
    initDashboardCharts();
}

export { floodActivityMockData, getFloodActivityData };
