# Project Atlas — Allocation Engine Simulator

A small, self-contained simulator for the Project Atlas allocation rule (FRD §7.2): when
several franchise stores can serve the same customer, the opportunity is **not** handed to
the cheapest or nearest store. Instead:

1. **Eligibility** — the store must be active, have stock, and its service area must cover
   the buyer's postcode (a simplified stand-in for FRD §7.3's postal-code regions).
2. **Price guard** — a store more than *tolerance %* above the lowest eligible price is
   excluded. Tolerance is the one interactive business lever.
3. **Score-weighted allocation** — the remaining stores share opportunities in proportion
   to their operational score, balanced over time so the realised split converges to target.
4. **Audit** — every decision records who was eligible, the prices, scores, tolerance,
   ceiling, exclusions, the winner, and a plain-English + technical explanation.

The default scenario is the FRD worked example: stores A/B/C with scores 5/4/3 →
target shares **41.7% / 33.3% / 25%**.

## Stack

Laravel 12 · Livewire 3 · MySQL · Tailwind (Vite) · Chart.js — all assets bundled
locally, **no internet required at runtime**. No CDN links anywhere in the app.

## Setup

```bash
composer install
npm install

cp .env.example .env          # if you don't have a .env yet
php artisan key:generate

# .env already points at MySQL db `allocation_engine_simulator` on 127.0.0.1 (root / no password).
# Create the database, then:
php artisan migrate --seed

npm run build                 # or: npm run dev
php artisan serve
```

Open http://127.0.0.1:8000 — it lands on the Dashboard / Simulator with the Front Brake
Disc selected at 10% tolerance.

### Reset the demo data

```bash
php artisan atlas:reset --force     # drops everything, re-migrates, re-seeds (3 stores, 5 products, 15 offers)
```

Use this between run-throughs so every rehearsal (and the real thing on the 15th) starts
from the same clean state — no leftover simulation runs or decision history on screen.

### Offline check

The app makes no external requests. To confirm: build assets (`npm run build`), start
`php artisan serve`, disable networking, then load every screen and run a simulation.

**Before you do this, make sure `public/hot` does not exist.** That file only appears
while `npm run dev` is running, and if it's left behind, Laravel points every page at the
Vite dev server instead of the built `public/build/assets/` bundle — which silently
reintroduces a live network dependency, exactly what this check exists to catch. If it's
there:

```bash
rm public/hot
npm run build
```

Then re-run the offline sweep. `php artisan test` also fails two assertions
(`AppShellTest`, `ReferenceScreensTest`) if `public/hot` is present, so a red test suite
after a `npm run dev` session is a hint to check for it.

## Tests

```bash
php artisan test
```

Covers the allocation maths (spec Tests 1–7), the stateful strategy and its convergence,
session-stable featured store, the simulation runner + audit persistence, the charts data,
the allocation logs, and both explanation registers. All screens are also swept for a
clean 200 response and no leaked internal names (class, method or column names) in the
customer-facing copy.

## Navigation

| Screen | Answers |
| --- | --- |
| **Dashboard / Simulator** (`/`) | What would happen right now if a customer wanted this product? |
| **Stores** (`/stores`, `/stores/{store}`) | Who are the sellers? Includes a "View glossary" modal explaining every field in plain language and technical terms. |
| **Products** (`/products`, `/products/{product}`) | What products exist and what does each store offer? Read-only reference data, deliberately never ranked or highlighted by price (FR-PDP-009) — the allocation itself only happens on the Dashboard. |
| **Allocation Logs** (`/allocations`, `/simulations`) | What did the system decide before, and why? |

## 5-minute demo script

1. **Open the Dashboard.** Front Brake Disc, three stores, scores 5 / 4 / 3.
2. **Explain the target split**: 41.7 / 33.3 / 25 — score-weighted, not cheapest-wins.
3. **Slider at 10%.** All three stores eligible (ceiling R$517 vs lowest R$470).
4. **Change the buyer postcode** to `08500-000`. Only Store C's service area covers it —
   the other two drop out before price or score are even looked at ("Outside service
   area", not a price exclusion). Set it back to `01310-100` to restore all three.
5. **Run 1,000 purchases.** Show the target-vs-realised bar chart and the convergence line
   chart — realised share tracks the dashed target lines.
6. **Drop tolerance to 2%.** Stores A and B fall outside the price guard; Store C becomes
   the sole winner. Re-run to show 100% C.
7. **Set tolerance to 0%.** The zero-tolerance warning appears (Keep 0% / Return to previous).
8. **Open Allocation Logs → a decision.** Every store's price, score, distance, eligibility,
   exclusion reason and target share, the winner, and the business + technical explanation.
9. **Back on the Dashboard, click Reload / Re-evaluate.** The featured store stays put —
   session-stable. **Use this button, not an actual browser refresh, once you've moved the
   slider** — `tolerance` (and now `buyerPostcode`) are plain in-memory values, not saved to
   the URL or session, so a real page reload resets both to their defaults instead of
   keeping whatever you set. The button re-runs the evaluation at whatever's currently on
   screen without losing it, which is the only way to demonstrate stability at non-default
   settings.

## Known gotchas

- **`public/hot` breaks offline mode.** See "Offline check" above — delete it and rebuild
  if it's ever present before a demo or a test run.
- **Neither the tolerance slider nor the buyer postcode survives a real browser reload.**
  Both are plain Livewire properties (`Dashboard::$tolerance`, `Dashboard::$buyerPostcode`),
  not bound to the URL or session, so a full page reload always remounts at their defaults
  (10%, `01310-100`). Only the *featured-store pick* is session-persisted (per product +
  tolerance + survivor-set, which already folds in the postcode's effect on eligibility),
  which is what FR-GEO-005 actually requires. Use Reload / Re-evaluate to prove stability
  without losing your current slider/postcode settings.
- **Alpine.js is only loaded on Livewire pages.** It ships bundled inside Livewire's own
  script, which Laravel only injects on pages that render a Livewire component (the
  Dashboard, Allocation Logs). The Stores and Products screens are plain Blade controller
  views with no Livewire component, so `x-data`/`x-on`/Alpine directives silently do
  nothing there. The store glossary modal (`x-store-glossary`) is deliberately built with
  a small vanilla-JS `<script>` block instead, so it works without Alpine — follow that
  pattern for any new interactive UI on a non-Livewire screen.

## Layout

- `app/Allocation/` — the engine (`AllocationEngine`, `EligibilityService`, `PriceGuardService`),
  the stateful strategy (`DeficitAllocationStrategy`, `RealisedState`, `AllocationStateStore`),
  `FeaturedStoreResolver`, `SimulationService`, `AllocationExplanationService`, and their DTOs.
- `App\Models\Store::coversPostcode()` — the simplified FR-RGN-001/002 stand-in: a store's
  `service_postcode_prefixes` (comma-separated CEP prefixes) checked against the buyer's
  postcode. Null prefixes or a null postcode mean "unrestricted," so every store/scenario
  created before this existed keeps working unchanged.
- `app/Livewire/Dashboard.php` — the simulator screen (controls only; all logic is in the services).
- `app/Livewire/AllocationLog/Index.php`, `app/Http/Controllers/` — reference and log screens.
- `resources/views/livewire/` — the Dashboard and Allocation Log screens.
- `resources/views/components/` — shared UI: `x-money`, `x-badge`, `x-seller-status`,
  `x-explanation` (plain + technical registers), `x-tolerance-help-modal` (price-band
  visual on the Dashboard), `x-store-glossary` (field reference modal on Stores), and the
  allocation-flow diagram components.
- `database/seeders/DemoDataSeeder.php` — the fixed demo dataset.
- `app/Console/Commands/ResetDemo.php` — `php artisan atlas:reset`.
