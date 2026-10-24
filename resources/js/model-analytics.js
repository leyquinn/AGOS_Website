import {
    BarController,
    BarElement,
    CategoryScale,
    Chart,
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
    Legend,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
);

const modelAnalyticsMockData = {
    'v1.0': {
        metrics: { accuracy: 0.82, precision: 0.78, recall: 0.75, f1: 0.77 },
        comparison: [0.82, 0.78, 0.75, 0.77],
        confusion: [[350, 100], [95, 456]],
        performance: [[0.82, 0.78, 0.80], [0.76, 0.72, 0.74], [0.84, 0.80, 0.82], [0.70, 0.67, 0.68]],
    },
    'v1.1': {
        metrics: { accuracy: 0.90, precision: 0.87, recall: 0.85, f1: 0.86 },
        comparison: [0.90, 0.87, 0.85, 0.86],
        confusion: [[398, 52], [44, 536]],
        performance: [[0.90, 0.86, 0.88], [0.84, 0.80, 0.82], [0.92, 0.89, 0.90], [0.76, 0.72, 0.74]],
    },
    'v1.2': {
        metrics: { accuracy: 0.94, precision: 0.91, recall: 0.89, f1: 0.90 },
        comparison: [0.94, 0.91, 0.89, 0.90],
        confusion: [[412, 38], [29, 551]],
        performance: [[0.94, 0.91, 0.92], [0.87, 0.83, 0.85], [0.96, 0.95, 0.95], [0.79, 0.74, 0.76]],
    },
};

const versions = ['v1.0', 'v1.1', 'v1.2'];
const chartInstances = new Map();
const chartColors = {
    baseline: '#9bb5e1',
    integrated: '#2563eb',
    grid: 'rgba(71, 107, 159, 0.25)',
    text: '#64748b',
};

const chartOptions = (scales = {}) => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 450, easing: 'easeOutQuart' },
    plugins: {
        legend: { labels: { color: chartColors.text, boxWidth: 10, font: { size: 9 } } },
        tooltip: { callbacks: { label: (context) => `${context.dataset.label}: ${context.parsed.y.toFixed(2)}` } },
    },
    scales: {
        x: { grid: { display: false }, ticks: { color: chartColors.text, font: { size: 9 } } },
        y: { beginAtZero: true, max: 1, grid: { color: chartColors.grid }, ticks: { color: chartColors.text, font: { size: 9 }, callback: (value) => value.toFixed(1) } },
        ...scales,
    },
});

const renderModelCharts = (analytics) => {
    const comparisonCanvas = analytics.querySelector('[data-model-chart="comparison"]');
    const f1Canvas = analytics.querySelector('[data-model-chart="f1"]');
    const current = modelAnalyticsMockData[analytics.querySelector('[data-model-version]').value];
    if (!comparisonCanvas || !f1Canvas || !current) return;

    [comparisonCanvas, f1Canvas].forEach((canvas) => chartInstances.get(canvas)?.destroy());
    chartInstances.set(comparisonCanvas, new Chart(comparisonCanvas, {
        type: 'bar',
        data: {
            labels: ['Accuracy', 'Precision', 'Recall', 'F1-score'],
            datasets: [
                { label: 'Baseline (classification only)', data: modelAnalyticsMockData['v1.0'].comparison, backgroundColor: chartColors.baseline, borderRadius: 4 },
                { label: 'Integrated (MTL + K-Means++)', data: current.comparison, backgroundColor: chartColors.integrated, borderRadius: 4 },
            ],
        },
        options: chartOptions(),
    }));
    chartInstances.set(f1Canvas, new Chart(f1Canvas, {
        type: 'line',
        data: {
            labels: versions.map((version) => version === 'v1.2' ? 'v1.2 (current)' : `${version}`),
            datasets: [{ label: 'F1 score', data: versions.map((version) => modelAnalyticsMockData[version].metrics.f1), borderColor: chartColors.integrated, backgroundColor: 'rgba(37,99,235,.12)', pointBackgroundColor: chartColors.integrated, pointRadius: 4, tension: 0.3, fill: true }],
        },
        options: chartOptions(),
    }));
};

const updatePerformanceTable = (analytics, performance) => {
    analytics.querySelectorAll('[data-performance-row]').forEach((row, rowIndex) => {
        ['precision', 'recall', 'f1'].forEach((metric, metricIndex) => {
            const cell = row.querySelector(`[data-performance="${metric}"]`);
            if (cell) cell.textContent = performance[rowIndex][metricIndex].toFixed(2);
        });
    });
};

const initModelAnalytics = () => {
    const analytics = document.querySelector('[data-model-analytics]');
    if (!analytics) return;

    const versionSelect = analytics.querySelector('[data-model-version]');
    const update = () => {
        const data = modelAnalyticsMockData[versionSelect.value];
        Object.entries(data.metrics).forEach(([key, value]) => {
            const metric = analytics.querySelector(`[data-model-metric="${key}"]`);
            if (metric) metric.textContent = value.toFixed(2);
        });
        const confusionValues = data.confusion.flat();
        analytics.querySelectorAll('[data-confusion]').forEach((cell) => {
            cell.textContent = confusionValues[Number(cell.dataset.confusion)];
        });
        updatePerformanceTable(analytics, data.performance);
        renderModelCharts(analytics);
    };
    versionSelect.addEventListener('change', update);
    update();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initModelAnalytics);
} else {
    initModelAnalytics();
}
