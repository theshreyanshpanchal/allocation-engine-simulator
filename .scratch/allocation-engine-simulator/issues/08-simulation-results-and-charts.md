# 08: Simulation results & charts

**What to build:** After a run, the dashboard shows the outcome visually: a bar chart comparing each store's target share against its realised share, and a line chart showing how the realised shares move toward the targets as the purchase count grows. Enough to let someone see at a glance that allocation is intentional, not random.

**Blocked by:** 07.

**Status:** done

- [x] Target-vs-realised bar chart, one grouped pair per store.
- [x] Convergence line chart: realised share per store across the run (by purchase index or sampled buckets), with target reference lines.
- [x] Per-store readout: target %, realised %, difference in percentage points, allocation count.
- [x] Charting library is bundled locally; no CDN, renders offline, no visible lag on a 1,000-purchase run.
- [x] Charts update after each run and clear/reset sensibly when the product or tolerance changes.
- [x] Include the price-guard impact summary (times each store was excluded by the guard vs. simply having a lower score) if it fits without stretching the ticket.

## Comments

- **Chart.js 4** installed via npm and bundled through Vite (`resources/js/app.js`) — no CDN. Bundle ~260 KB (90 KB gz).
- `SimulationService::convergenceSeries(AllocationRun, maxPoints=60)` — walks the run's decisions in order, keeps running counts, samples ~60 evenly-spaced points plus the final one; returns `labels`, per-store `series` (realised % at each sample), `targets` (%), `names`.
- `Dashboard::chartData()` — null before a run; after a run returns `bars` (labels + target[] + realised[] as %), `convergence` (the series above), and `priceGuardImpact` (excluded store names × purchase count). `simulate()` dispatches `simulation-updated` with the payload; product/tolerance change dispatches `simulation-cleared`.
- Frontend: an Alpine `simCharts()` component (registered on `alpine:init`, Alpine comes bundled with Livewire) in a `wire:ignore` container. Listens for the two window events, (re)builds/destroys a grouped **bar** chart (target vs realised %) and a **line** chart (realised share per store with dashed target reference lines). `animation:false` for a snappy 1000-purchase update. Per-run readout table stays from ticket 07.
- "Allocation impact" panel (spec §128): distinguishes share lost to the price guard (store excluded from every purchase) from share lost to a lower score (smaller target, still participating).
- Coverage: `tests/Feature/SimulationChartsTest.php` — 5 tests (convergence series shape + endpoint + near-target, null-then-full chartData, price-guard impact at 2%, event dispatch on simulate/clear, analytics section renders). Full suite 60 passed. Visual/offline browser render is exercised in ticket 11's offline pass.
