@props(['evaluation', 'tolerance'])

@php
    /** @var \App\Allocation\AllocationEvaluation $evaluation */
    /** @var \Illuminate\Support\Collection<int, \App\Allocation\SellerEvaluation> $sellers */
    $sellers = collect($evaluation->sellers ?? [])->sortBy(fn ($s) => $s->price)->values();
    $lowest = $evaluation->lowestPrice;
    $ceiling = $evaluation->priceCeiling;

    // A visual price scale with a little padding so the ceiling marker and every
    // store dot fit comfortably — computed from the real prices, not fixed values.
    $scaleMin = $scaleMax = null;
    if ($lowest !== null && $ceiling !== null) {
        $allPrices = $sellers->map(fn ($s) => $s->price)->push($ceiling)->filter();
        $scaleMin = $allPrices->min() * 0.92;
        $scaleMax = $allPrices->max() * 1.08;
        $scaleSpan = max(0.01, $scaleMax - $scaleMin);
        $positionOf = fn (float $price) => max(0, min(100, ($price - $scaleMin) / $scaleSpan * 100));
        $lowestPos = $positionOf($lowest);
        $ceilingPos = $positionOf($ceiling);
    }
@endphp

<div x-data>
    <button type="button" x-on:click="$refs.toleranceHelp.showModal()"
            class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-300 transition hover:text-brand-200">
        How does this work? →
    </button>

    {{-- Centered explicitly: Tailwind's Preflight zeroes every element's margin,
         which quietly breaks the browser's default `margin: auto` centering for
         <dialog>. Fixed positioning + inset + a negative-translate keeps it dead
         center regardless of where in the page this component is mounted. --}}
    <dialog x-ref="toleranceHelp"
            x-on:click="if ($event.target === $refs.toleranceHelp) $refs.toleranceHelp.close()"
            class="fixed top-1/2 left-1/2 m-0 max-h-[85vh] w-[90vw] max-w-2xl -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-2xl border border-ink-650 bg-ink-800 p-0 text-ink-100 backdrop:bg-ink-950/70">
        <div class="p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-ink-50">How the price tolerance guard works</h3>
                    <p class="mt-1 text-sm text-ink-300">Every store's real price, plotted on one line.</p>
                </div>
                <button type="button" x-on:click="$refs.toleranceHelp.close()"
                        class="shrink-0 rounded-lg p-1.5 text-ink-400 transition hover:bg-ink-750 hover:text-ink-100">
                    ✕
                </button>
            </div>

            <div class="mt-10">
                @if ($lowest !== null && $ceiling !== null)
                    <div class="relative h-2 rounded-full bg-ink-650">
                        {{-- the "allowed zone": from the cheapest price up to the ceiling --}}
                        <div class="absolute inset-y-0 rounded-full bg-emerald-400/30"
                             style="left: {{ $lowestPos }}%; width: {{ max(0, $ceilingPos - $lowestPos) }}%;"></div>

                        {{-- every store, plotted at its actual price --}}
                        @foreach ($sellers as $s)
                            @php $pos = $positionOf($s->price); @endphp
                            <div class="absolute -top-8 flex flex-col items-center" style="left: {{ $pos }}%; transform: translateX(-50%);">
                                <span class="whitespace-nowrap rounded-md px-1.5 py-0.5 font-mono text-[0.65rem] font-medium {{ $s->participating ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                    {{ $s->storeCode }}
                                </span>
                                <span class="mt-0.5 h-3 w-3 rounded-full border-2 border-ink-800 {{ $s->participating ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 flex justify-between text-xs text-ink-400">
                        <span>Lowest price<br><span class="font-mono font-semibold text-ink-100"><x-money :amount="$lowest" /></span></span>
                        <span class="text-right">Ceiling at {{ num($tolerance) }}%<br><span class="font-mono font-semibold text-ink-100"><x-money :amount="$ceiling" /></span></span>
                    </div>

                    <div class="mt-4 flex items-center gap-4 text-xs text-ink-300">
                        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span> In the zone — still in the running</span>
                        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span> Priced out — excluded here</span>
                    </div>
                @else
                    <p class="text-sm text-ink-300">No store is eligible right now, so there's no price band to show.</p>
                @endif
            </div>

            <div class="mt-6 space-y-2 text-sm leading-relaxed text-ink-300">
                <p>Every dot above is a real store, placed at its real price. The green band is the "allowed zone" — from the cheapest price up to the ceiling.</p>
                <p>A store inside the band is still in the running for the score-weighted split. A store outside it is left out for this product, however good its score is.</p>
                <p>Drag the slider on the dashboard left to shrink the band and be stricter on price. Drag it right to widen the band and let pricier stores compete too.</p>
            </div>
        </div>
    </dialog>
</div>
