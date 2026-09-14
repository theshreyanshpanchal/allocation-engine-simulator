# 09: Allocation Logs — history, filters, decision detail

**What to build:** A person can answer "what decisions did the system make before, and why?". A list of past allocation decisions, a way to filter it, and a detail view for any single decision that shows every store that was in play — its price, score, distance, whether it was eligible, why it was excluded if it was, its target share — and the winner. Also a list of past simulation runs.

**Blocked by:** 07.

**Status:** done

- [x] `/allocations` lists decisions: time, product, tolerance, winner, eligible-store count; newest first; paginated.
- [x] Basic filters: product, store (winner or involved), winner, tolerance value/range.
- [x] `/allocations/{decision}` shows the decision header (product, vehicle, buyer, time, tolerance, lowest eligible price, price ceiling) and a per-store block for every involved store (price, score, distance, eligibility, exclusion reason, target share), then the winner.
- [x] `/simulations` lists past runs (product, tolerance, purchase count, timestamps) linking to that run's decisions.
- [x] Excluded stores are visible in the detail view, not hidden.
- [x] All screens render offline and are reachable from the Allocation Logs nav item.

## Comments

- `/allocations` → Livewire `AllocationLog\Index` with `WithPagination` (25/page, `latest('id')`), `#[Url]` filters: product, winner store, involved store (`whereHas('sellers')`), tolerance value, and a `run` filter. `eligible_count` via `withCount('sellers as eligible_count' where price_guard_eligible)`.
- `/allocations/{decision}` → `AllocationController@show`: header cards (product/vehicle, time + run link + purchase #, buyer, tolerance, lowest price, ceiling), a card per store (price, score, distance, eligibility, target share, exclusion reason for excluded ones — kept visible), then the Winner block with `decision_reason`.
- `/simulations` → `SimulationController@index`: paginated runs (`RUN-0001` ref, started time, product, tolerance, purchases, decisions count) each linking to `/allocations?run={id}`.
- `AllocationDecision::reference` (`ALLOC-000001`) and `AllocationRun::reference` (`RUN-0001`) accessors. Sub-nav partial (`allocations._subnav`) toggles Decision history / Simulation runs; top nav "Allocation Logs" now active for `allocations.*` and `simulations.*`.
- Coverage: `tests/Feature/AllocationLogTest.php` — 9 tests (list columns, filter by product / winner / tolerance / run, detail shows all stores + prices + ceiling + winner, exclusion reasons on a 2% decision, runs list + decision link, nav reachability). Full suite 69 passed.
