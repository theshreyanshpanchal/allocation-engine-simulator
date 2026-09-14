# 03: Stores & Products reference screens

**What to build:** A person can browse the seeded stores and products through the UI to understand "who are the sellers?" and "what offers exist?". These are read-only reference screens; the allocation itself happens on the Dashboard, not here. Can be built in parallel with ticket 04.

**Blocked by:** 02.

**Status:** done

- [x] `/stores` lists the 3 stores as cards: operational score (x / 5), distance, status, product count, link to detail.
- [x] `/stores/{store}` shows name, code, operational score, distance, active/inactive, serviceability, and the store's offers (product, price, stock).
- [x] `/products` lists the 5 products with vehicle fitment and the per-store price columns (Store A / B / C).
- [x] `/products/{product}` shows the product with its vehicle fitment and an offers block: each store's price, score, distance, stock state.
- [x] All data comes from the seeded records; nothing on these screens is editable.
- [x] Screens are reachable from the primary nav and render offline.

## Comments

- `StoreController` (index/show) + `ProductController` (index/show) with route-model binding; routes added to `web.php`.
- Views: `stores/index`, `stores/show`, `products/index`, `products/show`. Shared anonymous components `x-money` (R$ formatting, `—` when null) and `x-badge` (semantic tones). `Store` gained `score_label` / `distance_label` accessors.
- Store/product detail pages cross-link. All read-only — no forms.
- Coverage: `tests/Feature/ReferenceScreensTest.php` — 5 tests. Also added `RefreshDatabase` + demo seed to `AppShellTest` since its nav-route checks now hit DB-backed pages. Full suite 18 passed.
