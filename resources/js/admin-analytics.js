import {
    Chart,
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';

Chart.register(
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Tooltip,
    Legend,
    Filler,
);

const initAnalyticsTrendChart = () => {
    const canvas = document.getElementById('analyticsTrendChart');
    if (!canvas) {
        return;
    }

    const dataAttr = canvas.dataset.series;
    if (!dataAttr) {
        return;
    }

    let series;
    try {
        series = JSON.parse(dataAttr);
    } catch (error) {
        return;
    }

    const labels = canvas.dataset.labelMap ? JSON.parse(canvas.dataset.labelMap) : {
        page_views: 'Page views',
        product_views: 'Product views',
        cart_adds: 'Cart adds',
        orders: 'Orders',
    };

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: series.labels,
            datasets: [
                {
                    label: labels.page_views,
                    data: series.datasets.page_views,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99,102,241,0.18)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 0,
                    borderWidth: 2,
                },
                {
                    label: labels.product_views,
                    data: series.datasets.product_views,
                    borderColor: '#22d3ee',
                    backgroundColor: 'rgba(34,211,238,0.10)',
                    fill: false,
                    tension: 0.35,
                    pointRadius: 0,
                    borderWidth: 2,
                },
                {
                    label: labels.cart_adds,
                    data: series.datasets.cart_adds,
                    borderColor: '#f43f5e',
                    backgroundColor: 'rgba(244,63,94,0.10)',
                    fill: false,
                    tension: 0.35,
                    pointRadius: 0,
                    borderWidth: 2,
                },
                {
                    label: labels.orders,
                    data: series.datasets.orders,
                    borderColor: '#34d399',
                    backgroundColor: 'rgba(52,211,153,0.10)',
                    fill: false,
                    tension: 0.35,
                    pointRadius: 0,
                    borderWidth: 2,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: 'rgba(255,255,255,0.7)', font: { size: 11, weight: '600' }, boxWidth: 10, padding: 14 },
                },
                tooltip: {
                    backgroundColor: 'rgba(7,7,64,0.95)',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                },
            },
            scales: {
                x: {
                    ticks: { color: 'rgba(255,255,255,0.45)', font: { size: 10 } },
                    grid: { color: 'rgba(255,255,255,0.05)' },
                },
                y: {
                    beginAtZero: true,
                    ticks: { color: 'rgba(255,255,255,0.45)', font: { size: 10 } },
                    grid: { color: 'rgba(255,255,255,0.05)' },
                },
            },
        },
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAnalyticsTrendChart, { once: true });
} else {
    initAnalyticsTrendChart();
}
