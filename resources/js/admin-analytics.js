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

const cssValue = (styles, property, fallback) => styles.getPropertyValue(property).trim() || fallback;

const readChartTheme = (canvas) => {
    const themeRoot = canvas.closest('.admin-shell') || document.documentElement;
    const styles = getComputedStyle(themeRoot);

    return {
        grid: cssValue(styles, '--admin-chart-grid', 'rgba(7,7,64,0.09)'),
        tick: cssValue(styles, '--admin-chart-tick', '#6b7383'),
        legend: cssValue(styles, '--admin-chart-legend', '#3e4358'),
        tooltipBackground: cssValue(styles, '--admin-chart-tooltip-bg', 'rgba(7,7,64,0.96)'),
        tooltipBorder: cssValue(styles, '--admin-chart-tooltip-border', 'rgba(255,255,255,0.14)'),
        primary: cssValue(styles, '--admin-chart-primary', '#4f46e5'),
        primaryFill: cssValue(styles, '--admin-chart-primary-fill', 'rgba(79,70,229,0.16)'),
        info: cssValue(styles, '--admin-chart-info', '#0891b2'),
        danger: cssValue(styles, '--admin-chart-danger', '#e11d48'),
        success: cssValue(styles, '--admin-chart-success', '#059669'),
    };
};

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
    const theme = readChartTheme(canvas);

    const chart = new Chart(canvas, {
        type: 'line',
        data: {
            labels: series.labels,
            datasets: [
                {
                    label: labels.page_views,
                    data: series.datasets.page_views,
                    borderColor: theme.primary,
                    backgroundColor: theme.primaryFill,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 0,
                    borderWidth: 2,
                },
                {
                    label: labels.product_views,
                    data: series.datasets.product_views,
                    borderColor: theme.info,
                    backgroundColor: theme.info,
                    fill: false,
                    tension: 0.35,
                    pointRadius: 0,
                    borderWidth: 2,
                },
                {
                    label: labels.cart_adds,
                    data: series.datasets.cart_adds,
                    borderColor: theme.danger,
                    backgroundColor: theme.danger,
                    fill: false,
                    tension: 0.35,
                    pointRadius: 0,
                    borderWidth: 2,
                },
                {
                    label: labels.orders,
                    data: series.datasets.orders,
                    borderColor: theme.success,
                    backgroundColor: theme.success,
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
                    labels: { color: theme.legend, font: { size: 11, weight: '600' }, boxWidth: 10, padding: 14 },
                },
                tooltip: {
                    backgroundColor: theme.tooltipBackground,
                    borderColor: theme.tooltipBorder,
                    borderWidth: 1,
                },
            },
            scales: {
                x: {
                    ticks: { color: theme.tick, font: { size: 10 } },
                    grid: { color: theme.grid },
                },
                y: {
                    beginAtZero: true,
                    ticks: { color: theme.tick, font: { size: 10 } },
                    grid: { color: theme.grid },
                },
            },
        },
    });

    const syncTheme = () => {
        const nextTheme = readChartTheme(canvas);
        const [pageViews, productViews, cartAdds, orders] = chart.data.datasets;

        pageViews.borderColor = nextTheme.primary;
        pageViews.backgroundColor = nextTheme.primaryFill;
        productViews.borderColor = nextTheme.info;
        productViews.backgroundColor = nextTheme.info;
        cartAdds.borderColor = nextTheme.danger;
        cartAdds.backgroundColor = nextTheme.danger;
        orders.borderColor = nextTheme.success;
        orders.backgroundColor = nextTheme.success;
        chart.options.plugins.legend.labels.color = nextTheme.legend;
        chart.options.plugins.tooltip.backgroundColor = nextTheme.tooltipBackground;
        chart.options.plugins.tooltip.borderColor = nextTheme.tooltipBorder;
        chart.options.scales.x.ticks.color = nextTheme.tick;
        chart.options.scales.x.grid.color = nextTheme.grid;
        chart.options.scales.y.ticks.color = nextTheme.tick;
        chart.options.scales.y.grid.color = nextTheme.grid;
        chart.update('none');
    };

    const observer = new MutationObserver(syncTheme);
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAnalyticsTrendChart, { once: true });
} else {
    initAnalyticsTrendChart();
}
