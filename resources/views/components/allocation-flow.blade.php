@props(['explanation'])

@php
    /** @var \App\Allocation\AllocationExplanation $explanation */
    $sellers = collect($explanation->sellers)->sortBy('code')->values();
    $eligible = collect($explanation->eligibleSellers())->sortBy('code')->values();
    $participating = collect($explanation->participatingSellers())->sortBy('code')->values();
    $totalScore = $explanation->totalScore();
    $tol = num($explanation->tolerancePercent);
@endphp

<div class="space-y-3">
    <p class="text-xs text-ink-400">
        Every store moves through the same three checks, left to right. A store drops out of the
        diagram at the stage where it was excluded, with the reason shown next to it.
    </p>

    <div class="flex flex-col items-stretch gap-2 sm:flex-row">
        {{-- Stage 1: every seller of this product --}}
        <div class="flex-1 rounded-xl border border-ink-650 bg-ink-750 p-3">
            <p class="text-[0.65rem] font-semibold uppercase tracking-widest text-ink-300">1. All sellers</p>
            <p class="mt-0.5 text-[0.65rem] text-ink-400">{{ count($sellers) }} store{{ count($sellers) === 1 ? '' : 's' }} offer this product</p>
            <div class="mt-2 space-y-1.5">
                @foreach ($sellers as $s)
                    <div class="flex items-center justify-between rounded-lg border border-ink-650 bg-ink-800 px-2.5 py-1.5 text-xs">
                        <span class="font-mono font-medium text-ink-100">{{ $s['code'] }}</span>
                        <span class="font-mono tabular-nums text-ink-400"><x-money :amount="$s['price']" /></span>
                    </div>
                @endforeach
            </div>
        </div>

        <x-allocation-flow-arrow />

        {{-- Stage 2: eligibility --}}
        <div class="flex-1 rounded-xl border border-ink-650 bg-ink-750 p-3">
            <p class="text-[0.65rem] font-semibold uppercase tracking-widest text-ink-300">2. Eligibility check</p>
            <p class="mt-0.5 text-[0.65rem] text-ink-400">Active, in stock, serves this buyer</p>
            <div class="mt-2 space-y-1.5">
                @foreach ($sellers as $s)
                    @if ($s['eligible'])
                        <div class="flex items-center justify-between rounded-lg border border-emerald-600/25 bg-emerald-50 px-2.5 py-1.5 text-xs">
                            <span class="font-mono font-medium text-emerald-800">{{ $s['code'] }}</span>
                            <span class="font-medium text-emerald-700">passed</span>
                        </div>
                    @else
                        <div class="rounded-lg border border-rose-600/25 bg-rose-50 px-2.5 py-1.5 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-mono font-medium text-rose-800">{{ $s['code'] }}</span>
                                <span class="font-medium text-rose-700">excluded</span>
                            </div>
                            <p class="mt-0.5 text-rose-700">{{ $s['exclusionReason'] }}</p>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <x-allocation-flow-arrow />

        {{-- Stage 3: price guard --}}
        <div class="flex-1 rounded-xl border border-ink-650 bg-ink-750 p-3">
            <p class="text-[0.65rem] font-semibold uppercase tracking-widest text-ink-300">3. Price guard</p>
            <p class="mt-0.5 text-[0.65rem] text-ink-400">
                @if ($explanation->lowestPrice !== null)
                    Ceiling = <x-money :amount="$explanation->lowestPrice" /> × (1 + {{ $tol }}%) = <x-money :amount="$explanation->priceCeiling" />
                @else
                    No eligible store to set a ceiling from
                @endif
            </p>
            <div class="mt-2 space-y-1.5">
                @forelse ($eligible as $s)
                    @if ($s['participating'])
                        <div class="flex items-center justify-between rounded-lg border border-emerald-600/25 bg-emerald-50 px-2.5 py-1.5 text-xs">
                            <span class="font-mono font-medium text-emerald-800">{{ $s['code'] }}</span>
                            <span class="font-mono tabular-nums text-emerald-700"><x-money :amount="$s['price']" /> ✓</span>
                        </div>
                    @else
                        <div class="rounded-lg border border-rose-600/25 bg-rose-50 px-2.5 py-1.5 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-mono font-medium text-rose-800">{{ $s['code'] }}</span>
                                <span class="font-mono tabular-nums text-rose-700"><x-money :amount="$s['price']" /></span>
                            </div>
                            <p class="mt-0.5 text-rose-700">more than {{ $tol }}% above the lowest price</p>
                        </div>
                    @endif
                @empty
                    <p class="text-xs text-ink-400">No store reached this stage.</p>
                @endforelse
            </div>
        </div>

        <x-allocation-flow-arrow />

        {{-- Stage 4: score-weighted split --}}
        <div class="flex-1 rounded-xl border border-ink-650 bg-ink-750 p-3">
            <p class="text-[0.65rem] font-semibold uppercase tracking-widest text-ink-300">4. Score-weighted split</p>
            <p class="mt-0.5 text-[0.65rem] text-ink-400">
                @if ($participating->isNotEmpty())
                    Total score {{ num($totalScore) }} — share = score ÷ total score
                @else
                    No store left to split between
                @endif
            </p>
            <div class="mt-2 space-y-2">
                @forelse ($participating as $s)
                    @php $isWinner = $s['id'] === $explanation->winnerId; @endphp
                    <div @class([
                        'rounded-lg border px-2.5 py-2 text-xs',
                        'border-brand-400 bg-brand-900' => $isWinner,
                        'border-ink-650 bg-ink-800' => ! $isWinner,
                    ])>
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-1 font-mono font-medium text-ink-100">
                                @if ($isWinner)<span title="Selected this time">★</span>@endif
                                {{ $s['code'] }}
                            </span>
                            <span class="font-mono tabular-nums text-ink-100">{{ pct($s['targetShare']) }}</span>
                        </div>
                        <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-ink-650">
                            <div class="h-full rounded-full bg-brand-400" style="width: {{ max(2, $s['targetShare'] * 100) }}%"></div>
                        </div>
                        <p class="mt-1 text-[0.65rem] text-ink-400">
                            {{ num($s['score']) }} ÷ {{ num($totalScore) }} = {{ pct($s['targetShare']) }}
                        </p>
                    </div>
                @empty
                    <p class="text-xs text-ink-400">No eligible store — nothing to split.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-brand-400/30 bg-brand-900 px-4 py-2.5 text-center text-sm font-semibold text-brand-300">
        Selected: {{ $explanation->winnerName ?? 'No eligible store' }}
    </div>
</div>
