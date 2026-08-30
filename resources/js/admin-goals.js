import {
    Chart, LineController, LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Legend, Filler,
} from 'chart.js';

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Legend, Filler);

const canvas = document.getElementById('goalsTrendChart');
if (canvas?.dataset.series) {
    try {
        const series = JSON.parse(canvas.dataset.series);
        const labels = JSON.parse(canvas.dataset.labels || '{}');
        new Chart(canvas, {
            type: 'line',
            data: {
                labels: series.labels,
                datasets: [
                    { label: labels.orders || 'Orders', data: series.orders, borderColor: '#FF6A00', backgroundColor: 'rgba(255,106,0,.12)', fill: true, tension: .35, pointRadius: 0, borderWidth: 2 },
                    { label: labels.revenue || 'Revenue', data: series.revenue, borderColor: '#070740', backgroundColor: 'transparent', tension: .35, pointRadius: 0, borderWidth: 2, yAxisID: 'revenue' },
                ],
            },
            options: {
                responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 7 } } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, grid: { color: 'rgba(100,116,139,.1)' } },
                    revenue: { beginAtZero: true, position: 'right', grid: { display: false }, ticks: { callback: (value) => Intl.NumberFormat(undefined, { notation: 'compact' }).format(value) } },
                },
            },
        });
    } catch (_) {
        // Keep the server-rendered dashboard usable if chart data is unavailable.
    }
}
