<x-layouts.app :title="$product->name . ' — Products'">
    <div class="mb-6">
        <a href="{{ route('products.index') }}" class="back-link">&larr; All products</a>
        <h1 class="page-title mt-2">{{ $product->name }}</h1>
        <p class="page-subtitle">{{ $product->vehicle_label }}</p>
    </div>

    <h2 class="section-title">Offers</h2>
    <p class="mt-1 text-xs text-ink-400">
        Reference data — every store's price is shown for context only. It's never used to
        rank or highlight an offer here; the price guard and score-weighted split on the
        Dashboard decide who wins, not the lowest price.
    </p>
    <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($offers as $offer)
            <div class="offer-card">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-ink-50">{{ $offer->store->name }}</h3>
                    <a href="{{ route('stores.show', $offer->store) }}" class="font-mono text-xs font-medium uppercase tracking-wide text-brand-300 hover:text-brand-200">
                        {{ $offer->store->code }}
                    </a>
                </div>
                <p class="mt-2 font-mono text-2xl font-semibold tracking-tight text-ink-50"><x-money :amount="$offer->price" /></p>
                <dl class="mt-3 space-y-1.5 border-t border-ink-650 pt-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-ink-400">Score</dt>
                        <dd class="font-mono font-medium text-ink-50">{{ $offer->store->score_label }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-400">Distance</dt>
                        <dd class="font-mono font-medium text-ink-50">{{ $offer->store->distance_label }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-400">Stock</dt>
                        <dd class="font-medium {{ $offer->inStock() ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $offer->inStock() ? 'Available' : 'Out of stock' }}
                        </dd>
                    </div>
                </dl>
            </div>
        @endforeach
    </div>
</x-layouts.app>
