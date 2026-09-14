# 11: Demo polish & readiness

**What to build:** The simulator survives someone else sitting down and driving it, start to finish, with no internet and no developer help. This ticket closes the gaps: empty and loading states, the zero-tolerance warning interaction, graceful handling of dead-end scenarios, a clean first screen, a reliable way to reset the demo data, and a short written demo script.

**Blocked by:** 08, 10.

**Status:** done

- [x] Loading state while a simulation runs; empty states before any run and on list screens with no data.
- [x] Zero-tolerance warning is a clear, dismissible interaction (keep 0% / return to previous value), wording per spec §76.
- [x] Dead-end scenarios handled with a message, not an error: no eligible stores, all offers out of stock, single eligible store.
- [x] App opens on the Dashboard / Simulator with the Brake Disc default; no debug or test UI visible; no console errors.
- [x] A documented command resets the demo to the seeded state (e.g. `migrate:fresh --seed`), and a README section covers install, run, offline check, and the demo script (spec §46 / §106 sequence).
- [x] Full offline pass: load every screen, run a simulation, open a log, with the network disabled.
- [x] Walk the spec §148 demo-readiness checklist and tick each item.

## Comments

- **Loading**: `wire:loading` spinner + "Running N purchases…" text on the simulation section; button already flips to "Simulating…". **Empty states**: log list + runs list show a "run a simulation" prompt (from ticket 09); dashboard shows a prompt before any run.
- **Zero tolerance**: `updatingTolerance` stashes the last non-zero value; at 0% an amber panel offers **Keep 0%** (`keepZeroTolerance` → collapses to a slim persistent banner) and **Return to previous value** (`restorePreviousTolerance` → restores it). Wording per §76.
- **Dead ends**: winnerless evaluations render a friendly amber "No allocation is possible right now" + the specific reason + a hint; single-participant renders a "its score is not the reason" note. No exceptions surface.
- **Reset**: `php artisan atlas:reset [--force]` (wraps `migrate:fresh --seed`, prints a summary). README rewritten: overview of the rule, stack, setup, reset, offline check, `php artisan test`, nav table, and the 8-step 5-minute demo script (§46/§106).
- **Offline**: built assets, `php artisan serve`, swept `/`, `/stores`, `/stores/{id}`, `/products`, `/products/{id}`, `/allocations`, `/allocations?tolerance=…`, `/simulations` — all 200, zero external hosts in the HTML. Chart.js + Alpine `simCharts` confirmed in the local bundle; Vite build clean (no JS errors). Simulation + log-open paths covered by the suite.
- Coverage: `tests/Feature/DemoReadinessTest.php` — 8 tests (default screen, zero-tolerance both choices, acknowledged banner, winnerless message, single-store note, list empty states, every route 200 with no "Whoops", `atlas:reset` registered). **Full suite: 84 passed (376 assertions).**
