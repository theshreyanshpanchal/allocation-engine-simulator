# 05: Live simulator dashboard — single evaluation

**What to build:** The main screen. A person selects a product, sees a demo buyer postal code, and drags a price-tolerance slider. As the slider moves, the screen live-updates the lowest price, price ceiling, which stores are eligible vs excluded (with plain status text), the recalculated target allocation, and the current featured store — no page reload. This ticket covers the controls and the single-evaluation readout; running N purchases and charts come later.

**Blocked by:** 04.

**Status:** done

- [x] Livewire dashboard at `/` with a product selector; default selection is Front Brake Disc — Toyota Corolla 2020.
- [x] Demo buyer postal code is shown (fixed value, no real postal lookup).
- [x] Price-tolerance slider: range 0–20%, step 0.5% or 1%, default 10%. It is the visually prominent control.
- [x] Moving the slider immediately recomputes via the ticket 04 engine and updates: lowest eligible price, price ceiling, per-store rows (price, score, distance, eligible/excluded with semantic text like "Excluded — price outside tolerance"), and target allocation percentages.
- [x] Number-of-purchases input is present on the screen (wired to nothing yet — execution is ticket 07).
- [x] Current featured store is displayed (basic selection here; session-stable behaviour is ticket 06).
- [x] Zero-tolerance selection shows the required administrator warning ("cheapest always wins").
- [x] Summary cards: selected product, lowest price + store, tolerance + max allowed, eligible sellers count, featured store, target allocation.
- [x] Colour is never the only signal — every state has text.
- [x] Does not depend on the Stores/Products screens (ticket 03).

## Comments

- `Dashboard` Livewire component: `productId` (defaults to `BRAKE-DISC-001`), `tolerance` (10.0, `wire:model.live` range 0–20 step 0.5, clamped + step-snapped in `updatedTolerance`), `purchaseCount` (1000), readonly `buyerPostcode`. `#[Computed]` `evaluation()` calls `AllocationEngine` each render.
- `featuredStore()` is a **provisional** pick (highest target share, tie-break score then code) — ticket 06 swaps in the session-stable stateful selection.
- View sections A–E per spec §44; six summary cards per §45; eligibility table with `x-seller-status` badge (icon + text, never colour alone); zero-tolerance amber warning; distance-is-context note. "Simulate purchases" button rendered disabled (wired in ticket 07).
- Added `app/Support/helpers.php` (`num()`, `pct()`) via composer `autoload.files`. Added `x-seller-status` component.
- Removed the stock `ExampleTest.php` (Feature + Unit) — the Feature one asserted `/` 200 without seeding and now conflicts with the DB-backed dashboard; `AppShellTest` covers `/` properly.
- Coverage: `tests/Feature/DashboardTest.php` — 7 tests (defaults, featured = Store A, live recompute at 2%, zero-tolerance warning, slider clamping, product switch, no dependency on ticket 03). Full suite 36 passed.
