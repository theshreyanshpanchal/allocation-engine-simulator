<div class="space-y-8">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="page-eyebrow"><span class="live-dot"></span> Live simulator</p>
            <h1 class="page-title">Allocation Simulator</h1>
            <p class="page-subtitle">What would happen right now if a customer wanted this product?</p>
        </div>
    </div>

    {{-- Section A — Context --------------------------------------------------- --}}
    <div class="grid gap-4 sm:grid-cols-2">
        <label class="block">
            <span class="field-label">Selected product</span>
            <select wire:model.live="productId" class="field-control">
                @foreach ($this->products as $option)
                    <option value="{{ $option->id }}">{{ $option->name }} — {{ $option->vehicle_label }}</option>
                @endforeach
            </select>
        </label>
        <label class="block">
            <span class="field-label">Buyer postcode</span>
            <input type="text" wire:model.live.debounce.400ms="buyerPostcode" class="field-control font-mono">
        </label>
    </div>

    <p class="-mt-4 text-xs text-ink-400">
        <span class="font-semibold text-ink-300">What this does:</span>
        a store only enters the running if its service area covers this postcode — checked
        before price or score. Try <span class="font-mono">02000-000</span> (Store A only),
        <span class="font-mono">08000-000</span> (Store C only), or
        <span class="font-mono">99999-999</span> (no store can serve it) to see the effect.
    </p>

    @php $evaluation = $this->evaluation; @endphp

    {{-- Section B — Business control: the price tolerance slider ------------- --}}
    @php
        $toleranceMin = \App\Livewire\Dashboard::TOLERANCE_MIN;
        $toleranceMax = \App\Livewire\Dashboard::TOLERANCE_MAX;
        $tolerancePercent = max(0, min(100, ((float) $tolerance - $toleranceMin) / ($toleranceMax - $toleranceMin) * 100));
    @endphp
    <div class="panel-brand">
        <div class="flex items-center justify-between">
            <h2 class="page-eyebrow">Price tolerance guard</h2>
            <span class="readout">{{ num($tolerance) }}<span class="text-base font-semibold">%</span></span>
        </div>

        <input type="range" wire:model.live.debounce.120ms="tolerance"
               min="{{ $toleranceMin }}"
               max="{{ $toleranceMax }}"
               step="{{ \App\Livewire\Dashboard::TOLERANCE_STEP }}"
               style="background: linear-gradient(to right, var(--color-brand-500) {{ $tolerancePercent }}%, var(--color-ink-650) {{ $tolerancePercent }}%)"
               class="slider mt-6">
        <div class="slider-ticks mt-1.5"></div>
        <div class="mt-1.5 flex justify-between font-mono text-xs font-medium text-ink-400">
            <span>{{ (int) $toleranceMin }}%</span>
            <span>{{ (int) $toleranceMax }}%</span>
        </div>

        @if ($evaluation && $evaluation->lowestPrice !== null)
            <div class="mt-5 grid gap-3 text-sm sm:grid-cols-2">
                <div class="rounded-xl border border-brand-400/20 bg-ink-950/50 px-3 py-2">
                    <span class="block text-[0.7rem] uppercase tracking-widest text-ink-400">Lowest eligible price</span>
                    <span class="text-base font-semibold text-ink-50"><x-money :amount="$evaluation->lowestPrice" /></span>
                </div>
                <div class="rounded-xl border border-brand-400/20 bg-ink-950/50 px-3 py-2">
                    <span class="block text-[0.7rem] uppercase tracking-widest text-ink-400">Maximum allowed price</span>
                    <span class="text-base font-semibold text-ink-50"><x-money :amount="$evaluation->priceCeiling" /></span>
                </div>
            </div>

            {{-- What this control actually does, in both registers — reads off the live numbers
                 above so it always matches whatever the slider is currently set to. --}}
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-ink-650 bg-ink-800 p-4">
                    <p class="flex items-center gap-1.5 text-[0.7rem] font-semibold uppercase tracking-widest text-ink-300">
                        🛡️ What this does
                    </p>
                    <p class="mt-2.5 text-base font-semibold text-ink-50">
                        Only stores priced at or below
                        <x-money :amount="$evaluation->priceCeiling" class="font-semibold text-brand-300" />
                        qualify right now.
                    </p>
                    <p class="mt-2 text-sm leading-relaxed text-ink-300">
                        This slider sets how much pricier a store is allowed to be and still get a share of
                        customers. A store above that line is left out, no matter how good its score is.
                        Drag it left to be stricter on price; drag it right to let pricier stores compete too.
                    </p>
                    <x-tolerance-help-modal :evaluation="$evaluation" :tolerance="$tolerance" />
                </div>

                <div class="rounded-xl border border-ink-650 bg-ink-800 p-4">
                    <p class="flex items-center gap-1.5 text-[0.7rem] font-semibold uppercase tracking-widest text-ink-300">
                        🧮 The formula
                    </p>

                    <div class="mt-2.5 rounded-lg border border-ink-650 bg-ink-950/40 p-3">
                        <p class="font-mono text-xs text-ink-400">ceiling = lowest price × (1 + tolerance ÷ 100)</p>
                        <p class="mt-2 flex flex-wrap items-center gap-x-1.5 gap-y-1 font-mono text-sm text-ink-100">
                            <x-money :amount="$evaluation->priceCeiling" class="font-semibold text-brand-300" />
                            <span class="text-ink-400">=</span>
                            <x-money :amount="$evaluation->lowestPrice" />
                            <span class="text-ink-400">×</span>
                            <span>(1 + {{ num($tolerance) }}% ÷ 100)</span>
                        </p>
                    </div>

                    <p class="mt-2.5 text-xs leading-relaxed text-ink-400">
                        Any store priced above the ceiling is excluded — reason "price outside tolerance" —
                        before scores are ever compared.
                    </p>
                </div>
            </div>
        @endif

        @if ($this->isZeroTolerance() && ! $zeroToleranceAcknowledged)
            <div class="mt-4 panel-warning">
                <p class="font-semibold">⚠ Price guard warning</p>
                <p class="mt-1">
                    Zero tolerance means cheapest always wins. Score-based allocation will only apply when
                    multiple stores share the same lowest price.
                </p>
                <div class="mt-3 flex gap-2">
                    <button type="button" wire:click="keepZeroTolerance" class="btn-warning btn-sm">
                        Keep 0%
                    </button>
                    <button type="button" wire:click="restorePreviousTolerance" class="btn-warning-ghost btn-sm">
                        Return to previous value
                    </button>
                </div>
            </div>
        @elseif ($this->isZeroTolerance())
            <div class="mt-4 panel-warning py-3 text-xs">
                ⚠ Zero tolerance active — cheapest always wins unless stores share the lowest price.
            </div>
        @endif
    </div>

    @if (! $evaluation)
        <p class="text-sm text-ink-400">Select a product to run the simulator.</p>
    @elseif (! $evaluation->hasParticipatingSellers())
        <div class="panel-warning p-6">
            <p class="font-semibold">No allocation is possible right now</p>
            <p class="mt-1">{{ $evaluation->winnerlessReason() }}</p>
            <p class="mt-2 text-xs">Try widening the price tolerance or selecting a different product.</p>
        </div>
    @else
        @if ($evaluation->isSingleParticipant())
            <div class="rounded-xl border border-ink-650 bg-ink-800 p-3 text-xs text-ink-300">
                Only one store is inside the price guard, so it takes 100% of the allocation — its score is not the reason.
            </div>
        @endif
        @php $featured = $this->featuredStore; @endphp

        {{-- Summary cards — spec section 45 -------------------------------- --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="stat-tile">
                <p class="stat-label">Selected product</p>
                <p class="stat-value text-base">{{ $this->product->name }}</p>
                <p class="stat-sub">{{ $this->product->vehicle_label }}</p>
            </div>
            <div class="stat-tile">
                <p class="stat-label">Lowest price</p>
                <p class="stat-value"><x-money :amount="$evaluation->lowestPrice" /></p>
                @php $cheapest = collect($evaluation->sellers)->sortBy('price')->first(); @endphp
                <p class="stat-sub">{{ $cheapest?->storeName }}</p>
            </div>
            <div class="stat-tile">
                <p class="stat-label">Price ceiling</p>
                <p class="stat-value"><x-money :amount="$evaluation->priceCeiling" /></p>
                <p class="stat-sub">at {{ num($tolerance) }}% tolerance</p>
            </div>
            <div class="stat-tile">
                <p class="stat-label">Eligible sellers</p>
                <p class="stat-value">
                    {{ count($evaluation->participating()) }} / {{ count($evaluation->sellers) }}
                </p>
            </div>
            <div class="stat-tile">
                <p class="stat-label">Featured store</p>
                <p class="stat-value text-base">{{ $featured?->storeName ?? '—' }}</p>
                <p class="stat-sub">@if ($featured) Score {{ num($featured->score) }} @endif</p>
            </div>
            <div class="stat-tile">
                <p class="stat-label">Target allocation</p>
                <div class="mt-2 space-y-1 text-sm">
                    @foreach ($evaluation->participating() as $seller)
                        <div class="flex justify-between">
                            <span class="font-mono text-ink-300">{{ $seller->storeCode }}</span>
                            <span class="font-mono font-medium tabular-nums text-ink-50">{{ pct($seller->targetShare) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Section C — Eligibility table -------------------------------- --}}
        <div>
            <h2 class="section-title">Eligible stores</h2>
            <div class="mt-3 table-shell">
                <table class="table-base">
                    <thead class="table-head">
                        <tr>
                            <th>Store</th>
                            <th class="text-right">Price</th>
                            <th class="text-right">Score</th>
                            <th class="text-right">Distance</th>
                            <th class="text-right">Target share</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($evaluation->sellers as $seller)
                            @php $isFeatured = $featured?->storeId === $seller->storeId; @endphp
                            <tr class="{{ $isFeatured ? 'bg-brand-500/[0.07]' : '' }}">
                                <td class="font-medium text-ink-50">{{ $seller->storeName }}</td>
                                <td class="text-right"><x-money :amount="$seller->price" /></td>
                                <td class="text-right font-mono tabular-nums">{{ num($seller->score) }}</td>
                                <td class="text-right font-mono tabular-nums">{{ num($seller->distanceKm) }} km</td>
                                <td class="text-right font-mono tabular-nums">{{ $seller->participating ? pct($seller->targetShare) : '—' }}</td>
                                <td>
                                    <div class="flex flex-wrap gap-1">
                                        <x-seller-status :seller="$seller" />
                                        @if ($isFeatured)<x-badge tone="info">Featured</x-badge>@endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-2 text-xs text-ink-400">
                Distance is shown for context only — it never decides eligibility or who wins.
                Whether a store can serve the buyer comes from its service area against the
                buyer postcode above, plus whether it's open and in stock.
            </p>
        </div>

        {{-- Section D — Allocation / featured + session stability ------- --}}
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="card p-5">
                <p class="stat-label">Current featured store</p>
                <p class="mt-1.5 text-lg font-semibold text-ink-50">{{ $featured?->storeName ?? '—' }}</p>
                @if ($featured)
                    <p class="text-sm text-ink-400">Target share {{ pct($featured->targetShare) }}</p>
                @endif
            </div>
            <div class="card p-5">
                <p class="stat-label">Session stability</p>
                <dl class="mt-2 space-y-1 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-ink-400">Session</dt>
                        <dd class="font-mono text-xs text-ink-200">{{ strtoupper(substr(session()->getId(), 0, 12)) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-400">Featured store</dt>
                        <dd class="font-medium text-ink-50">{{ $featured?->storeName ?? '—' }}</dd>
                    </div>
                </dl>
                <button type="button" wire:click="reevaluate" class="btn-secondary btn-sm mt-3">
                    Reload / Re-evaluate
                </button>
                <p class="mt-2 text-xs leading-relaxed text-ink-300">
                    <span class="font-semibold text-ink-200">What this does:</span>
                    it copies what happens if this same customer closes the page and comes back later.
                    They'll see the same store again — not a new pick every time they check — as long as
                    the product and the price tolerance haven't changed.
                </p>
                <p class="mt-1.5 text-xs text-ink-500">
                    Technical: re-runs the evaluation but reads the previously-cached pick from the
                    session instead of drawing a new one.
                </p>
            </div>
        </div>

        {{-- Section G — Explanation ----------------------------------- --}}
        @if ($this->explanation)
            <x-explanation :explanation="$this->explanation" />
        @endif

        {{-- Section E — Simulation ------------------------------------- --}}
        <div class="card p-5">
            <h2 class="section-title">Run a simulation</h2>
            <div class="mt-3 flex flex-wrap items-end gap-4">
                <label class="block">
                    <span class="field-label">Number of simulated purchases</span>
                    <input type="number" wire:model.live="purchaseCount" min="1" max="100000" step="1"
                           class="field-control w-40 font-mono">
                </label>
                <button type="button" wire:click="simulate" wire:loading.attr="disabled" wire:target="simulate" class="btn-primary">
                    <span wire:loading.remove wire:target="simulate">Simulate purchases</span>
                    <span wire:loading wire:target="simulate">Simulating…</span>
                </button>
                <span wire:loading wire:target="simulate" class="text-xs text-ink-400">
                    Running {{ number_format(max(1, (int) $purchaseCount)) }} purchases through the engine…
                </span>
            </div>

            <div wire:loading.flex wire:target="simulate" class="mt-4 items-center gap-2 text-sm text-ink-400">
                <svg class="h-4 w-4 animate-spin text-brand-400" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                Simulating…
            </div>

            @php $outcome = $this->outcome; @endphp
            @if ($outcome)
                <div class="mt-5 border-t border-ink-650 pt-4">
                    <p class="text-sm text-ink-200">
                        Simulation completed —
                        <span class="font-mono font-semibold text-ink-50">{{ number_format($outcome->purchaseCount) }}</span> purchases
                        at <span class="font-mono font-semibold text-ink-50">{{ num($outcome->tolerancePercent) }}%</span> tolerance.
                    </p>

                    <div class="mt-3 table-shell">
                        <table class="table-base">
                            <thead class="table-head">
                                <tr>
                                    <th>Store</th>
                                    <th class="text-right">Target</th>
                                    <th class="text-right">Realised</th>
                                    <th class="text-right">Difference</th>
                                    <th class="text-right">Allocations</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($outcome->rows as $row)
                                    <tr>
                                        <td class="font-medium text-ink-50">{{ $row['storeName'] }}</td>
                                        <td class="text-right font-mono tabular-nums">{{ pct($row['targetShare']) }}</td>
                                        <td class="text-right font-mono tabular-nums">{{ pct($row['realisedShare']) }}</td>
                                        <td class="text-right font-mono tabular-nums {{ $row['differencePoints'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                            {{ $row['differencePoints'] >= 0 ? '+' : '' }}{{ number_format($row['differencePoints'], 2) }} pp
                                        </td>
                                        <td class="text-right font-mono tabular-nums">{{ number_format($row['allocations']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-2 text-xs text-ink-400">
                        The full decision log for this run lives under Allocation Logs.
                    </p>
                </div>
            @endif
        </div>

        {{-- Section F — Analytics ------------------------------------- --}}
        <div wire:ignore
             x-data="simCharts(@js($this->chartData()))"
             x-on:simulation-updated.window="render($event.detail.payload)"
             x-on:simulation-cleared.window="clear()">
            <div x-show="bar || conv" class="space-y-6" style="display:none">
                <div class="card p-5">
                    <h2 class="section-title">Target vs realised</h2>
                    <div class="mt-3 h-64"><canvas x-ref="bar"></canvas></div>
                </div>
                <div class="card p-5">
                    <h2 class="section-title">Convergence over time</h2>
                    <p class="text-xs text-ink-400">
                        Realised share moves toward the dashed target lines as purchases accumulate —
                        the allocation is intentional, not a random draw.
                    </p>
                    <div class="mt-3 h-72"><canvas x-ref="conv"></canvas></div>
                </div>
            </div>
        </div>

        @php $outcome = $this->outcome; @endphp
        @if ($outcome)
            <div class="card p-5">
                <h2 class="section-title">Allocation impact</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-ink-400">Price guard</dt>
                        <dd class="text-right text-ink-100">
                            @if (count($outcome->priceGuardExclusions) === 0)
                                No store was excluded by the price guard.
                            @else
                                @foreach ($outcome->priceGuardExclusions as $storeId => $count)
                                    @php $s = $this->evaluation?->sellerFor($storeId); @endphp
                                    <div>{{ $s?->storeName ?? 'A store' }} excluded from all {{ number_format($count) }} purchases</div>
                                @endforeach
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-400">Score allocation</dt>
                        <dd class="max-w-md text-right text-ink-100">
                            Among participating stores, opportunities are split by operational score —
                            a lower score means a smaller target share, not exclusion.
                        </dd>
                    </div>
                </dl>
            </div>
        @endif
    @endif
</div>
