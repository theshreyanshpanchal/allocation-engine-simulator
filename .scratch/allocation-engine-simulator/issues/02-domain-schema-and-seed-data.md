# 02: Domain schema & seed data

**What to build:** Running `php artisan migrate:fresh --seed` produces the entire persistent structure the simulator needs and the canonical demo dataset. This ticket owns **all** database/schema/migration work for the feature — the reference tables (stores, products, offers) and the audit/simulation tables (runs, decisions, per-seller decision rows, allocation state) — so no later ticket adds migrations. Only the reference tables get seeded data; the audit tables are created empty and filled at runtime by ticket 07.

**Blocked by:** 01.

**Status:** done

- [x] Migrations + Eloquent models for `stores`, `products`, `product_offers`.
- [x] `stores`: code, name, operational_score, distance_km, status, serviceable.
- [x] `products`: sku, name, brand, vehicle make/model/year, status.
- [x] `product_offers`: product_id, store_id, price, stock_quantity, status — price and stock live here, not on `products`.
- [x] Migrations for the audit/simulation tables, created empty: `allocation_runs`, `allocation_decisions`, `allocation_decision_sellers`, `allocation_state`.
- [x] `allocation_decisions` can store: product, buyer context, tolerance, lowest price, price ceiling, winner, decision reason, timestamp.
- [x] `allocation_decision_sellers` can store per seller: price, score, distance, stock available, serviceable, price-guard eligibility, exclusion reason, target share.
- [x] Seeder creates exactly 3 stores: Store A score 5 / 5 km, Store B score 4 / 8 km, Store C score 3 / 12 km — all active and serviceable.
- [x] Seeder creates exactly 5 products, all fitted to Toyota Corolla 2020: Front Brake Disc, Front Brake Pad Set, Engine Oil Filter, Engine Air Filter, Cabin Air Filter.
- [x] Seeder creates 15 offers (every product × every store) with the fixed price matrix from the spec (Brake Disc 500/480/470, Pad Set 320/310/300, Oil Filter 75/72/70, Air Filter 110/105/100, Cabin Filter 95/92/90) and all in stock.
- [x] Prices/scores/distances are fixed seed values; no editable-pricing UI is built. Models/tables are shaped so editing could be added later.
- [x] A test asserts the seeded dataset matches the spec (store count, scores, product count, offer count, the Brake Disc price row).
- [x] `migrate:fresh --seed` is idempotent and re-runnable.

## Comments

- 7 migrations (`database/migrations/2026_09_03_1300{01..07}_*`): stores, products, product_offers, allocation_runs, allocation_decisions, allocation_decision_sellers, allocation_state. Renamed from the artisan defaults to force FK-safe ordering.
- Models: `Store`, `Product`, `ProductOffer`, `AllocationRun`, `AllocationDecision`, `AllocationDecisionSeller`, `AllocationState` with relationships + casts. `AllocationState` pins `$table = 'allocation_state'` (singular).
- `allocation_decisions` also carries nullable `purchase_index`, `eligible_context`, `human_explanation`, `technical_explanation` so tickets 07 and 10 need no further migrations. `allocation_decision_sellers` also has `is_winner`.
- MySQL 64-char identifier limit: gave explicit short names to the composite unique indexes (`ads_decision_store_unique`, `allocation_state_unique`).
- Seeder: `DemoDataSeeder` (called from `DatabaseSeeder`), `updateOrCreate` throughout so re-runs are idempotent. Offers seeded with `stock_quantity = 100`. Verified with tinker; `migrate:fresh --seed` green against MySQL.
- Coverage: `tests/Feature/DemoDataSeederTest.php` — 5 tests (store scores/distances, product fitment + names, 15 in-stock offers, Brake Disc price row, idempotency). Full suite 13 passed.
