@php
    /**
     * A plain-language walkthrough of the whole allocation flow — every check a
     * store passes through when a customer wants to buy a product, in the order
     * the engine applies them. Generic (no scenario numbers): it explains the
     * pipeline itself so a viewer can follow any scenario on the page against it.
     *
     * Self-contained <dialog> + a small vanilla-JS snippet, because the pages
     * that use it (/scenarios) render no Livewire component, so Alpine is not
     * loaded there. Scoped to a unique id in case it ever renders twice.
     */
    $stages = [
        [
            'n' => '1',
            'tag' => 'Start',
            'title' => 'A customer wants a product',
            'body' => 'The customer picks a product and we know their postcode. We begin with every store that sells this product.',
            'drops' => [],
        ],
        [
            'n' => '2',
            'tag' => 'Eligibility',
            'title' => 'Can this store serve this customer at all?',
            'body' => 'Three checks, in this order. If a store fails any one of them, it is out here — before its price or its score are ever looked at.',
            'checks' => [
                'Is the store open for business?',
                'Does it have this product in stock right now?',
                "Does its delivery area cover the customer's postcode?",
            ],
            'drops' => [
                '“Store is inactive” — the store is closed',
                '“Out of stock” — this product is not available at that store',
                '“Outside service area” — the store does not deliver to this postcode',
            ],
        ],
        [
            'n' => '3',
            'tag' => 'Price guard',
            'title' => 'Is the price close enough to the cheapest?',
            'body' => 'Take the lowest price among the stores still in. The limit is that lowest price plus the tolerance percent set by the slider. A store priced above the limit stays visible as an alternative, but cannot be the featured offer.',
            'note' => 'Tolerance at 0% means only the cheapest store stays in — unless other stores match that exact lowest price.',
            'drops' => [
                '“Price outside tolerance” — the store is priced above the limit',
            ],
        ],
        [
            'n' => '4',
            'tag' => 'Fair share',
            'title' => 'Share the customers by performance',
            'body' => 'The stores that are left share the customers in proportion to their performance score. A store\'s share = its score ÷ the total score of the stores left. Equal scores means an equal split. A lower score means a smaller share — not exclusion.',
            'drops' => [],
        ],
        [
            'n' => '5',
            'tag' => 'Over time',
            'title' => 'Keep the real split on target',
            'body' => 'Across many customers, the next customer goes to whichever store is furthest behind its target share. So the real split tracks the target instead of drifting. It is deliberate, not a random draw.',
            'drops' => [],
        ],
        [
            'n' => '6',
            'tag' => 'Result',
            'title' => 'Show the offer and record why',
            'body' => 'One store is shown as the featured offer; the others stay visible as alternatives. The whole decision is saved — who was eligible, their prices and scores, the limit in force, who was excluded and why, and who won. If the same customer reloads during the same visit, they see the same featured store.',
            'drops' => [],
        ],
    ];

    $modalId = 'allocation-process-'.uniqid();
@endphp

<div>
    <div class="card flex items-center justify-between gap-4 p-5">
        <div>
            <h2 class="section-title">How does a store get picked?</h2>
            <p class="mt-1 text-xs text-ink-400">
                The full flow, step by step — the checks every store passes through, in order. Opens in a modal.
            </p>
        </div>
        <button type="button" data-process-open="{{ $modalId }}" class="btn-secondary btn-sm shrink-0">
            View the buying flow →
        </button>
    </div>

    {{-- Centered explicitly — see the note in store-glossary: Preflight zeroes the
         margin the browser relies on to centre a <dialog>. --}}
    <dialog id="{{ $modalId }}" data-process-dialog
            class="fixed top-1/2 left-1/2 m-0 max-h-[88vh] w-[92vw] max-w-2xl -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-2xl border border-ink-650 bg-ink-800 p-0 text-ink-100 backdrop:bg-ink-950/70">
        <div class="p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-ink-50">How a store gets picked</h3>
                    <p class="mt-1 text-sm text-ink-300">
                        Every scenario on this page runs through these same steps, top to bottom.
                    </p>
                </div>
                <button type="button" data-process-close="{{ $modalId }}"
                        class="shrink-0 rounded-lg p-1.5 text-ink-400 transition hover:bg-ink-750 hover:text-ink-100">
                    ✕
                </button>
            </div>

            <div class="mt-5 space-y-2">
                @foreach ($stages as $i => $stage)
                    <div class="rounded-xl border border-ink-650 bg-ink-750 p-4">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-500/15 font-mono text-xs font-semibold text-brand-300">
                                {{ $stage['n'] }}
                            </span>
                            <h4 class="text-sm font-semibold text-ink-50">{{ $stage['title'] }}</h4>
                            <span class="ml-auto rounded-full border border-ink-600 bg-ink-800 px-2 py-0.5 text-[0.6rem] font-semibold uppercase tracking-widest text-ink-400">
                                {{ $stage['tag'] }}
                            </span>
                        </div>

                        <p class="mt-2 text-sm leading-relaxed text-ink-100">{{ $stage['body'] }}</p>

                        @if (! empty($stage['checks']))
                            <ol class="mt-2.5 space-y-1 text-sm text-ink-100">
                                @foreach ($stage['checks'] as $ci => $check)
                                    <li class="flex gap-2">
                                        <span class="font-mono text-xs text-ink-400">{{ $ci + 1 }}.</span>
                                        <span>{{ $check }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        @endif

                        @if (! empty($stage['note']))
                            <p class="mt-2.5 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs leading-relaxed font-medium text-amber-800">
                                {{ $stage['note'] }}
                            </p>
                        @endif

                        @if (! empty($stage['drops']))
                            <div class="mt-2.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs leading-relaxed text-rose-700">
                                <p class="font-semibold text-rose-800">A store drops out here if:</p>
                                <ul class="mt-1 space-y-0.5 font-medium">
                                    @foreach ($stage['drops'] as $drop)
                                        <li>— {{ $drop }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    @unless ($loop->last)
                        <div class="flex justify-center py-0.5 text-ink-500">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    @endunless
                @endforeach
            </div>

            <p class="mt-5 rounded-lg border border-ink-650 bg-ink-800 px-3 py-2.5 text-xs leading-relaxed text-ink-200">
                Steps 2 and 3 only decide whether a store is <em>in the running</em>. The performance
                score (step 4) only matters after that — it never rescues a store that failed an
                earlier check.
            </p>
        </div>
    </dialog>
</div>

<script>
    (function () {
        var dialog = document.getElementById(@json($modalId));
        if (!dialog) return;

        document.querySelectorAll('[data-process-open="' + @json($modalId) + '"]').forEach(function (btn) {
            btn.addEventListener('click', function () { dialog.showModal(); });
        });

        dialog.querySelectorAll('[data-process-close="' + @json($modalId) + '"]').forEach(function (btn) {
            btn.addEventListener('click', function () { dialog.close(); });
        });

        dialog.addEventListener('click', function (event) {
            if (event.target === dialog) dialog.close();
        });
    })();
</script>
