# 01: Project foundation & app shell

**What to build:** A person can open the app locally with no internet connection and land on a working shell. The top navigation shows four destinations — Dashboard / Simulator, Stores, Products, Allocation Logs — and each one routes to a real (if still empty) page. Styling is in place and every asset (CSS, JS, fonts, future chart library) is served from the application itself, never a CDN.

**Blocked by:** None (can start immediately).

**Status:** done

- [x] Livewire (latest) is installed and configured; a trivial Livewire component renders on a page.
- [x] A base Blade layout exists with Tailwind compiled through the existing Vite pipeline; `npm run build` produces local assets and the app renders correctly with them.
- [x] No CDN or external network requests are made by any page (verified with devtools offline / network panel).
- [x] Primary nav renders on every page: Dashboard / Simulator (`/`), Stores (`/stores`), Products (`/products`), Allocation Logs (`/allocations`).
- [x] Each nav route resolves to a placeholder page with a heading; the active item is visually indicated.
- [x] App boots with `php artisan serve` + `npm run dev` (or built assets) against the existing MySQL config without errors.
- [x] MySQL connection settings in `.env` are left unchanged.

## Comments

- Livewire 3 installed (`livewire/livewire`). Root route `/` is the `Dashboard` full-page Livewire component; `/stores`, `/products`, `/allocations` are placeholder Blade views. Shared layout at `resources/views/components/layouts/app.blade.php` with the four-item nav and active-state highlighting.
- Removed the default `welcome.blade.php` (referenced bunny.net fonts) and stale `public/hot`. Custom `@theme` font in `app.css` switched to a system stack so nothing is fetched from Google/Bunny.
- **Config change:** this WAMP MySQL 8.3 install defaults new tables to MyISAM, which broke the base `users` migration (`1071 key too long`). Pinned the `mysql` connection to `'engine' => 'InnoDB'` in `config/database.php`. `.env` untouched.
- Coverage: `tests/Feature/AppShellTest.php` — 6 tests (Livewire mount, all nav routes 200, nav items present, active marker, local-only assets). Full suite green (8 passed).
