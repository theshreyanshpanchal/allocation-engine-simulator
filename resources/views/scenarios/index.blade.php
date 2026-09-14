<x-layouts.app title="Scenario Playbook — Allocation Engine Simulator">
    <div class="mb-6">
        <p class="page-eyebrow">// Explainer</p>
        <h1 class="page-title">Scenario Playbook</h1>
        <p class="page-subtitle">
            Eleven hand-picked cases, each changing one input — tolerance, a store's status, or the
            buyer's postcode — to isolate one rule of the engine at a time. Every result below is
            computed live by the same engine the Dashboard uses; nothing here is typed in by hand.
        </p>
    </div>

    <div class="mb-6">
        <x-allocation-process-modal />
    </div>

    <div class="space-y-6">
        @foreach ($scenarios as $scenario)
            @php
                /** @var \App\Allocation\ScenarioResult $scenario */
                $evaluation = $scenario->evaluation;
            @endphp
            <div class="card p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-ink-50">{{ $scenario->title() }}</h2>
                        <p class="mt-1 max-w-3xl text-sm leading-relaxed text-ink-300">{{ $scenario->summary() }}</p>
                    </div>
                    <div class="flex shrink-0 flex-wrap justify-end gap-1.5">
                        @foreach ($scenario->frIds() as $frId)
                            <span class="rounded-full border border-ink-600 bg-ink-800 px-2 py-0.5 font-mono text-[0.65rem] uppercase tracking-widest text-ink-400">{{ $frId }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-4 border-t border-ink-650 pt-3 text-xs text-ink-400">
                    <span>Tolerance <span class="font-mono font-semibold text-ink-100">{{ num($scenario->definition->tolerancePercent) }}%</span></span>
                    @if ($scenario->definition->buyerPostcode)
                        <span>Buyer postcode <span class="font-mono font-semibold text-ink-100">{{ $scenario->definition->buyerPostcode }}</span></span>
                    @endif
                    @if ($evaluation->lowestPrice !== null)
                        <span>Lowest price <span class="font-mono font-semibold text-ink-100"><x-money :amount="$evaluation->lowestPrice" /></span></span>
                        <span>Ceiling <span class="font-mono font-semibold text-ink-100"><x-money :amount="$evaluation->priceCeiling" /></span></span>
                    @endif
                </div>

                <div class="mt-4 table-shell">
                    <table class="table-base">
                        <thead class="table-head">
                            <tr>
                                <th>Store</th>
                                <th class="text-right">Price</th>
                                <th class="text-right">Score</th>
                                <th>Status</th>
                                <th class="text-right">Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($evaluation->sellers as $seller)
                                <tr>
                                    <td class="font-medium text-ink-50">{{ $seller->storeName }}</td>
                                    <td class="text-right"><x-money :amount="$seller->price" /></td>
                                    <td class="text-right font-mono tabular-nums">{{ num($seller->score) }}</td>
                                    <td><x-seller-status :seller="$seller" /></td>
                                    <td class="text-right font-mono tabular-nums">
                                        {{ $seller->participating ? pct($seller->targetShare) : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 rounded-xl border border-brand-400/20 bg-ink-950/50 px-3 py-2.5 text-sm">
                    <span class="font-semibold text-ink-100">Result:</span>
                    <span class="text-ink-200">{{ $scenario->resultLine() }}</span>
                </div>

                <details class="mt-3 group">
                    <summary class="flex cursor-pointer items-center gap-1 text-sm font-medium text-brand-400 transition hover:text-brand-300">
                        <svg class="h-3.5 w-3.5 transition group-open:rotate-90" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                        </svg>
                        Explain this one
                    </summary>
                    <div class="mt-3">
                        <x-explanation :explanation="$scenario->explanation" title="What happened here" />
                    </div>
                </details>
            </div>
        @endforeach
    </div>
</x-layouts.app>
