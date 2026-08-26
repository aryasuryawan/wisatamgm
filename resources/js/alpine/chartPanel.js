import Chart from 'chart.js/auto';

/**
 * Alpine wrapper Chart.js.
 * Pemakaian:
 *   <canvas x-data="chartPanel" data-chart='{"type":"line","data":{...},"options":{...}}'></canvas>
 * JSON dikirim server (Blade) — tidak ada logic bisnis di sini.
 */
export default function (Alpine) {
    Alpine.data('chartPanel', () => ({
        init() {
            const raw = this.$el.dataset.chart;
            if (!raw) return;

            let config;
            try {
                config = JSON.parse(raw);
            } catch (e) {
                console.error('Invalid chart JSON', e);
                return;
            }

            config.options = {
                responsive: true,
                maintainAspectRatio: false,
                ...(config.options || {}),
            };

            new Chart(this.$el, config);
        },
    }));
}
