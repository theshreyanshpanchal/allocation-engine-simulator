# 07: Run N purchases — simulation runner & audit persistence

**What to build:** A person enters a purchase count on the dashboard, clicks Run, and the system executes that many purchases through the engine (ticket 04) and the stateful strategy (ticket 06), recording every decision. When it finishes, the screen shows how many allocations each store received and the realised share. Every individual decision is written with enough detail to reconstruct why it happened. Uses the audit tables already created in ticket 02 — no new migrations.

**Blocked by:** 06.

**Status:** done

- [x] `SimulationService` runs N purchases: for each, evaluate eligibility + price guard, compute targets, select via the stateful strategy, update state, record the decision.
- [x] A run is persisted to `allocation_runs` (product, tolerance, purchase count, started/completed timestamps).
- [x] Each purchase is persisted to `allocation_decisions` (lowest price, tolerance, price ceiling, winner, decision reason, timestamp) with child `allocation_decision_sellers` rows (price, score, distance, stock, serviceable, price-guard eligibility, exclusion reason, target share) for every store involved — including excluded ones.
- [x] "Simulate Purchases" button on the dashboard is wired to the runner with the N input from ticket 05.
- [x] After a run, per-store allocation counts and realised shares are returned and displayed as numbers (charts are ticket 08).
- [x] Runs of 1,000 purchases complete quickly enough for a live demo.
- [x] Test: a run's decision + per-seller rows contain every audited field for a known scenario (spec Test 7).

## Comments

- `SimulationService::run(Product, tolerance, count, ?postcode): AllocationRun` — evaluates once (inputs are constant across the run), folds N purchases with **run-local** `RealisedState` starting empty (spec §31), picks each winner via `DeficitAllocationStrategy`. Whole thing in a DB transaction; decisions + seller rows bulk-inserted in 500-row chunks. 1000 purchases ≈ 0.8s, 4000 rows, converges to 417/333/250 exactly.
- `outcomeFor(AllocationRun): SimulationOutcome` — rebuilds per-store target/realised/difference-pp/allocations from the persisted decisions (grouped by `winner_store_id`) so the dashboard result survives Livewire round-trips. Also carries `priceGuardExclusions` for ticket 08's §128 panel.
- `AllocationStateStore::addRunTotals()` folds the run's win counts + purchase total into `allocation_state` for the context in one pass.
- Dashboard: `simulate()` action (guards on participating sellers), `#[Computed] outcome()`, `lastRunId` state, live "Simulating…" button label, results table (target / realised / ±pp / allocations). Changing product or tolerance clears the stale outcome.
- Winnerless runs still record the run + one winnerless decision with the reason.
- Coverage: `tests/Feature/SimulationServiceTest.php` — 9 tests (run timestamps, one decision per purchase numbered from 1, full audit row set incl. is_winner / spec Test 7, price-guard exclusion rows at 2%, 1000-purchase convergence < 2pp with allocations summing to N, `allocation_state` accumulation across runs, 2000-purchase under 5s, dashboard button drives it, stale-outcome clearing). Full suite 55 passed.
