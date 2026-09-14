# 04: Allocation engine core — eligibility, price guard, score targets

**What to build:** Given a product and a price-tolerance percentage, the engine returns which stores can participate, which are excluded and why, and the target allocation share for each participating store. This is pure business logic with no UI and no persistence — it is the piece every later ticket consumes. Target shares are always computed on the set that survives the price guard, never by removing stores from a precomputed 5/4/3 split.

**Blocked by:** 02.

**Status:** done

- [x] An eligibility step filters stores by: store active, product in stock at that store, store serviceable for the buyer.
- [x] A price-guard step computes lowest eligible price, price ceiling = lowest x (1 + tolerance/100), and marks each store pass/excluded with a reason string.
- [x] Score targets are computed over only the price-guard survivors: `target_share = store_score / sum(survivor_scores)`.
- [x] Default scenario: Front Brake Disc at 10% tolerance -> A, B, C all eligible -> 41.7% / 33.3% / 25.0%.
- [x] Narrow tolerance (2%) on Brake Disc -> A and B excluded, C eligible -> C = 100%.
- [x] Two-survivor case (e.g. 2.5% tolerance) -> B and C only -> 57.1% / 42.9%.
- [x] Zero tolerance -> only stores at the lowest price participate; if several share the lowest price they all participate and score-based allocation applies among them.
- [x] Equal scores -> equal target shares. One survivor -> 100%. No survivors -> engine returns an explicit "no eligible stores" result rather than erroring.
- [x] Excluded stores remain in the returned result (with price, score, exclusion reason) so downstream audit/UI can show them.
- [x] Unit tests cover spec Tests 1–5 plus the two-survivor and one-survivor scenarios.

## Comments

- `App\Allocation` namespace: `AllocationEngine::evaluate(Product, float $tolerancePercent): AllocationEvaluation`. Composes `EligibilityService` (active → in stock → serviceable, first failure wins the reason) and `PriceGuardService` (lowest eligible price, `round(lowest * (1 + tol/100), 2)` ceiling, half-cent epsilon on the compare).
- `SellerDraft` is the mutable build row; frozen into readonly `SellerEvaluation`s. `AllocationEvaluation` exposes `participating()`, `excluded()`, `excludedByPriceGuard()`, `targetShareFor()`, `isSingleParticipant()`, `winnerlessReason()`, and `eligibleContextKey()` (sorted `A|B|C` — for ticket 06 state scoping).
- Reason strings centralised in `ExclusionReason` so the audit + explanation tickets reuse them verbatim.
- Zero tolerance: ceiling collapses to the lowest price; ties at that price all stay in. All-zero scores fall back to an equal split.
- Factories added (`Store`, `Product`, `ProductOffer`) with `inactive()` / `notServiceable()` / `outOfStock()` / `price()` / `score()` states.
- Coverage: `tests/Feature/AllocationEngineTest.php` — 14 tests (spec Tests 1–5, two/one-survivor, inactive/out-of-stock exclusion, winnerless, equal scores, audit retention, context key). Full suite 31 passed.
