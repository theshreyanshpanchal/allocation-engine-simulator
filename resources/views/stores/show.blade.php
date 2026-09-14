<x-layouts.app :title="$store->name . ' — Stores'">
    <div class="mb-6">
        <a href="{{ route('stores.index') }}" class="back-link">&larr; All stores</a>
        <h1 class="page-title mt-2">{{ $store->name }}</h1>
        <p class="font-mono text-xs uppercase tracking-widest text-ink-400">{{ $store->code }}</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="stat-tile">
            <p class="stat-label">Operational score</p>
            <p class="stat-value">{{ $store->score_label }} / 5</p>
            <p class="stat-sub mt-1.5">Weighed against other eligible stores' scores to set this store's share of the traffic.</p>
        </div>
        <div class="stat-tile">
            <p class="stat-label">Distance from buyer</p>
            <p class="stat-value">{{ $store->distance_label }}</p>
            <p class="stat-sub mt-1.5">Shown for context only — it never decides which store wins an order.</p>
        </div>
        <div class="stat-tile">
            <p class="stat-label">Status</p>
            <p class="stat-value">{{ $store->isActive() ? 'Active' : 'Inactive' }}</p>
            <p class="stat-sub mt-1.5">
                {{ $store->isActive() ? 'Open for business — eligible to be considered for orders.' : "Closed — excluded from every order until it's reactivated." }}
            </p>
        </div>
        <div class="stat-tile">
            <p class="stat-label">Serviceability</p>
            <p class="stat-value text-base">
                {{ $store->serviceable ? 'Eligible for buyer' : 'Outside service area' }}
            </p>
            <p class="stat-sub mt-1.5">
                {{ $store->serviceable ? "Can deliver to this buyer's area." : "Can't deliver here — excluded regardless of price or score." }}
            </p>
        </div>
    </div>

    <div class="mt-8">
        <h2 class="section-title">Offers ({{ $store->offers->count() }})</h2>
        <p class="mt-1 text-xs text-ink-400">
            Price sets this store's standing against the price guard; stock decides whether it's in the running for that product at all.
        </p>
        <div class="mt-3 table-shell">
            <table class="table-base">
                <thead class="table-head">
                    <tr>
                        <th>Product</th>
                        <th>Vehicle</th>
                        <th class="text-right">Price</th>
                        <th class="text-right">Stock</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($store->offers->sortBy('product.name') as $offer)
                        <tr>
                            <td>
                                <a href="{{ route('products.show', $offer->product) }}" class="text-brand-300 hover:text-brand-200">
                                    {{ $offer->product->name }}
                                </a>
                            </td>
                            <td class="text-ink-400">{{ $offer->product->vehicle_label }}</td>
                            <td class="text-right"><x-money :amount="$offer->price" /></td>
                            <td class="text-right">
                                @if ($offer->inStock())
                                    <span class="font-mono tabular-nums text-ink-50">{{ $offer->stock_quantity }}</span>
                                @else
                                    <span class="font-medium text-rose-600">Out of stock</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-8">
        <x-store-glossary />
    </div>
</x-layouts.app>
