import './bootstrap';
import { Chart } from 'chart.js/auto';

/**
 * Two charts for a finished simulation run:
 *  - a grouped bar chart: target % vs realised % per store
 *  - a line chart: realised share per store across the run, with dashed
 *    target reference lines
 *
 * Driven by Livewire browser events so it updates after each run and clears
 * when the product or tolerance changes. Chart.js is bundled locally — no CDN.
 */
const STORE_COLORS = ['#9c7cf8', '#22d3ee', '#fbbf24', '#fb7185', '#34d399'];
const AXIS_LABEL = '#a6abc9';
const AXIS_TICK = '#7f84a8';
const AXIS_GRID = 'rgba(255, 255, 255, 0.06)';
const LEGEND_TEXT = '#d4d6e7';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('simCharts', (initial = null) => ({
        bar: null,
        conv: null,

        init() {
            if (initial) {
                this.$nextTick(() => this.render(initial));
            }
        },

        destroy() {
            this.bar?.destroy();
            this.conv?.destroy();
            this.bar = null;
            this.conv = null;
        },

        clear() {
            this.destroy();
        },

        render(payload) {
            if (!payload) {
                this.clear();
                return;
            }
            this.renderBar(payload.bars);
            this.renderConvergence(payload.convergence);
        },

        renderBar(bars) {
            const canvas = this.$refs.bar;
            if (!canvas || !bars) return;

            const data = {
                labels: bars.labels,
                datasets: [
                    { label: 'Target %', data: bars.target, backgroundColor: 'rgba(156, 124, 248, 0.25)', borderRadius: 6, maxBarThickness: 36 },
                    { label: 'Realised %', data: bars.realised, backgroundColor: '#9c7cf8', borderRadius: 6, maxBarThickness: 36 },
                ],
            };

            if (this.bar) {
                this.bar.data = data;
                this.bar.update();
                return;
            }

            this.bar = new Chart(canvas, {
                type: 'bar',
                data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Share of opportunities (%)', color: AXIS_LABEL },
                            ticks: { color: AXIS_TICK },
                            grid: { color: AXIS_GRID },
                        },
                        x: {
                            ticks: { color: LEGEND_TEXT },
                            grid: { display: false },
                        },
                    },
                    plugins: {
                        legend: { position: 'bottom', labels: { color: LEGEND_TEXT, usePointStyle: true, boxWidth: 8 } },
                    },
                },
            });
        },

        renderConvergence(conv) {
            const canvas = this.$refs.conv;
            if (!canvas || !conv || !conv.labels || conv.labels.length === 0) return;

            const codes = Object.keys(conv.series);
            const datasets = [];

            codes.forEach((code, i) => {
                const color = STORE_COLORS[i % STORE_COLORS.length];
                datasets.push({
                    label: `${conv.names[code] ?? code} — realised`,
                    data: conv.series[code],
                    borderColor: color,
                    backgroundColor: color,
                    borderWidth: 2,
                    pointRadius: 0,
                    tension: 0.25,
                });
                datasets.push({
                    label: `${conv.names[code] ?? code} — target`,
                    data: conv.labels.map(() => conv.targets[code]),
                    borderColor: color,
                    borderWidth: 1,
                    borderDash: [6, 4],
                    pointRadius: 0,
                });
            });

            const data = { labels: conv.labels, datasets };

            if (this.conv) {
                this.conv.data = data;
                this.conv.update();
                return;
            }

            this.conv = new Chart(canvas, {
                type: 'line',
                data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    interaction: { intersect: false, mode: 'index' },
                    scales: {
                        x: {
                            title: { display: true, text: 'Purchases', color: AXIS_LABEL },
                            ticks: { color: AXIS_TICK },
                            grid: { color: AXIS_GRID },
                        },
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Realised share (%)', color: AXIS_LABEL },
                            ticks: { color: AXIS_TICK },
                            grid: { color: AXIS_GRID },
                        },
                    },
                    plugins: {
                        legend: { position: 'bottom', labels: { color: LEGEND_TEXT, usePointStyle: true, boxWidth: 8 } },
                    },
                },
            });
        },
    }));
});
