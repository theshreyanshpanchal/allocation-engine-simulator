# 06: Stateful allocation strategy & session-stable featured store

**What to build:** The rule that picks one winner per purchase, in a way that balances toward the target over time instead of drawing randomly each time. Also: the featured store shown on the dashboard stays the same when the page is reloaded or re-evaluated within a session, as long as the inputs haven't changed.

**Blocked by:** 04.

**Status:** done

- [x] A strategy selects, among price-guard survivors, the store with the largest positive `deficit = target_share - realised_share`.
- [x] Ties break deterministically (documented secondary rule, e.g. higher score then store code).
- [x] Allocation state (allocated count, total count, realised share) is tracked per product and per eligible-seller context, using the `allocation_state` table from ticket 02.
- [x] Selection is NOT a per-purchase random draw; given the same starting state it is reproducible.
- [x] The dashboard's featured store is stored in the session, keyed by product + tolerance + surviving-seller set.
- [x] Reloading the dashboard or clicking "Re-evaluate" with unchanged inputs returns the same featured store.
- [x] Changing the product or tolerance (which changes the survivor set) is allowed to produce a new featured store.
- [x] Test: within one session, featured store A stays A across reloads (spec Test 6).

## Comments

- `DeficitAllocationStrategy::select(AllocationEvaluation, RealisedState)` — picks the participating store with the largest `target_share - realised_share`; ties break by higher score then store code (`[$deficitB, $b->score, $a->storeCode] <=> [...]`). Reproducible: no randomness. Also exposes `deficitFor()` for the audit/explanation tickets.
- `RealisedState` — immutable counts+total; `withAllocation()` returns a new instance so a simulation folds cleanly.
- `AllocationStateStore` — reads/writes the `allocation_state` rows, scoped by `product_id` + `eligible_context` (the sorted `STORE-A|STORE-B|STORE-C` key from the evaluation). `recordAllocation()` bumps the winner's `allocated_count` and every participant's `total_count`; `reset()` clears a context.
- `FeaturedStoreResolver` — session-stable featured pick. Key = `featured:{productId}:{tolerance}:{contextKey}`. First call picks via the strategy against persisted state and caches the store id in the session; later calls return the cached store (validating it's still participating, else re-picking). Dashboard `reevaluate()` just busts the computed caches — the session keeps the pick steady.
- Dashboard now delegates `featuredStore()` to the resolver and shows a "Session stability" panel (session id, featured store, Reload / Re-evaluate button).
- Moved shared test fixtures (`makeScenario`, `makeBrakeDiscScenario`, `participatingSharesPercent`) into `tests/Pest.php`; refactored `AllocationEngineTest` onto them.
- Coverage: `DeficitAllocationStrategyTest` (7 — first pick, determinism, behind-target favouring, tie-break, 1200-purchase convergence < 1pp, RealisedState folding) + `FeaturedStoreStabilityTest` (4 — reload stability incl. fresh mount in same session, tolerance-change re-pick, stale-pick recovery, per-context persistence). Full suite 46 passed.
