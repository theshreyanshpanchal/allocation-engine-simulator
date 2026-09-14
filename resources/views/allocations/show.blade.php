<x-layouts.app :title="$decision->reference . ' — Allocation Logs'">
    <div class="mb-6">
        <a href="{{ route('allocations.index') }}" class="back-link">&larr; All decisions</a>
        <h1 class="page-title mt-2">Allocation decision <span class="font-mono text-brand-300">{{ $decision->reference }}</span></h1>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="stat-tile">
            <p class="stat-label">Product</p>
            <p class="stat-value text-base">{{ $decision->product->name }}</p>
            <p class="stat-sub">{{ $decision->product->vehicle_label }}</p>
        </div>
        <div class="stat-tile">
            <p class="stat-label">Time</p>
            <p class="stat-value text-base">{{ $decision->created_at->format('Y-m-d H:i:s') }}</p>
            @if ($decision->run)
                <a href="{{ route('allocations.index', ['run' => $decision->run->id]) }}"
                   class="text-xs font-medium text-brand-300 hover:text-brand-200">
                    part of {{ $decision->run->reference }} · purchase #{{ $decision->purchase_index }}
                </a>
            @endif
        </div>
        <div class="stat-tile">
            <p class="stat-label">Buyer</p>
            <p class="stat-value text-base">{{ $decision->buyer_postcode ?? 'Demo postal code' }}</p>
        </div>
        <div class="stat-tile">
            <p class="stat-label">Price tolerance</p>
            <p class="stat-value">{{ num($decision->tolerance_percent) }}%</p>
        </div>
        <div class="stat-tile">
            <p class="stat-label">Lowest eligible price</p>
            <p class="stat-value"><x-money :amount="$decision->lowest_price" /></p>
        </div>
        <div class="stat-tile">
            <p class="stat-label">Price ceiling</p>
            <p class="stat-value"><x-money :amount="$decision->price_ceiling" /></p>
        </div>
    </div>

    <div class="mt-8">
        <h2 class="section-title">Stores in this decision</h2>
        <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($sellers as $seller)
                <div @class([
                    'rounded-2xl border p-5 shadow-panel transition',
                    'border-brand-400/50 bg-gradient-to-br from-ink-800 to-brand-950/60 shadow-glow-brand' => $seller->is_winner,
                    'border-ink-650 bg-ink-800' => ! $seller->is_winner,
                ])>
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold text-ink-50">{{ $seller->store->name }}</h3>
                        @if ($seller->is_winner)
                            <x-badge tone="info">Winner</x-badge>
                        @elseif ($seller->price_guard_eligible)
                            <x-badge tone="success">Eligible</x-badge>
                        @else
                            <x-badge tone="danger">{{ $seller->exclusion_reason }}</x-badge>
                        @endif
                    </div>
                    <dl class="mt-3 space-y-1.5 border-t border-ink-650 pt-3 text-sm">
                        <div class="flex justify-between"><dt class="text-ink-400">Price</dt><dd class="font-mono font-medium text-ink-50"><x-money :amount="$seller->price" /></dd></div>
                        <div class="flex justify-between"><dt class="text-ink-400">Score</dt><dd class="font-mono font-medium text-ink-50">{{ num($seller->score) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-ink-400">Distance</dt><dd class="font-mono font-medium text-ink-50">{{ num($seller->distance_km) }} km</dd></div>
                        <div class="flex justify-between"><dt class="text-ink-400">Eligibility</dt><dd class="font-medium text-ink-50">{{ $seller->price_guard_eligible ? 'Eligible' : 'Excluded' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-ink-400">Target share</dt><dd class="font-mono font-medium text-ink-50">{{ $seller->price_guard_eligible ? pct($seller->target_share) : '—' }}</dd></div>
                        @unless ($seller->price_guard_eligible)
                            <div class="pt-1 text-ink-300">{{ $explanation->perSellerNotes[$seller->store_id] ?? $seller->exclusion_reason }}</div>
                        @endunless
                    </dl>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-8 card p-5">
        <h2 class="section-title">Winner</h2>
        <p class="mt-1 text-lg font-semibold text-ink-50">{{ $decision->winner?->name ?? 'No eligible store' }}</p>
        @if ($decision->decision_reason)
            <p class="mt-1 text-sm text-ink-300">{{ $decision->decision_reason }}</p>
        @endif
    </div>

    <div class="mt-8">
        <x-explanation :explanation="$explanation" title="Why this decision" />
    </div>
</x-layouts.app>
