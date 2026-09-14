@php
    /**
     * Fixed reference definitions for the fields shown across the Stores module.
     * Two registers side by side — read the plain-language column for a
     * non-technical audience, the technical column for an engineering one —
     * so nothing needs clicking mid-demo beyond opening this modal.
     */
    // Technical = the rule the engine follows, in plain conditions and math — never a
    // class, method, or field name. Plain = the same rule, in everyday words.
    $terms = [
        [
            'label' => 'Status (Active / Inactive)',
            'plain' => 'Whether the store is currently open for business. A closed store can never be picked, no matter how good its price or score.',
            'technical' => "A store must be marked active. This is the very first condition checked — a closed store is excluded before its stock, service area, price, or score are looked at.",
        ],
        [
            'label' => 'Serviceability',
            'plain' => "Whether the store is allowed to deliver to this particular customer's area. A store that can't serve the buyer is never in the running, whatever its price or score.",
            'technical' => "A store must be flagged as able to serve this buyer's area. Checked before price is looked at — failing it excludes the store for the reason 'outside service area', regardless of price or score.",
        ],
        [
            'label' => 'Stock',
            'plain' => "Whether the store currently has this specific product available to sell. Out of stock means it's skipped for that product, even if the store itself is open and serviceable.",
            'technical' => 'The specific product must be marked available with a quantity greater than zero at that store. Checked per product, right after the store-level open check.',
        ],
        [
            'label' => 'Price',
            'plain' => "What the store charges for this product. A store priced too far above the cheapest option is left out of the running, no matter how good its score is.",
            'technical' => 'The lowest price among stores that passed every check above sets a ceiling: ceiling = lowest price × (1 + tolerance ÷ 100). Anything priced above that ceiling is excluded before scores are ever compared.',
        ],
        [
            'label' => 'Operational score',
            'plain' => "A rating from 0 to 5 of how reliable and well-run a store is. The higher a store's score, the bigger the slice of customers it gets when several stores could serve the same order — but only among stores that already passed the price check above.",
            'technical' => "A number from 0 to 5. Among the stores that pass every check above, each one's share of the traffic = its score ÷ the sum of all their scores. It has no effect on whether a store passes the checks in the first place.",
        ],
        [
            'label' => 'Distance',
            'plain' => "How far the store is from the customer. It's shown for context, but it has no effect on which store wins the order.",
            'technical' => 'Purely informational — this number is never read by any of the store-open, service-area, stock, price, or scoring checks.',
        ],
        [
            'label' => 'Products',
            'plain' => 'How many different products this store currently offers.',
            'technical' => "A count of how many products this store currently lists, regardless of stock level.",
        ],
    ];

    // Unique per render (not per Alpine/Livewire component) — this component is used
    // on plain, non-Livewire pages (/stores, /products), so it can't rely on Alpine
    // being loaded. It manages its own <dialog> with a small vanilla-JS snippet
    // instead, scoped to this id in case the component ever renders twice on one page.
    $modalId = 'store-glossary-'.uniqid();
@endphp

<div>
    <div class="card flex items-center justify-between gap-4 p-5">
        <div>
            <h2 class="section-title">What do these terms mean?</h2>
            <p class="mt-1 text-xs text-ink-400">Plain language for a client, technical for an engineer — opens in a modal so it stays out of the way until needed.</p>
        </div>
        <button type="button" data-glossary-open="{{ $modalId }}" class="btn-secondary btn-sm shrink-0">
            View glossary →
        </button>
    </div>

    {{-- Centered explicitly: Tailwind's Preflight zeroes every element's margin,
         which quietly breaks the browser's default `margin: auto` centering for
         <dialog>. Fixed positioning + inset + a negative-translate keeps it dead
         center regardless of where in the page this component is mounted. --}}
    <dialog id="{{ $modalId }}" data-glossary-dialog
            class="fixed top-1/2 left-1/2 m-0 max-h-[85vh] w-[90vw] max-w-3xl -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-2xl border border-ink-650 bg-ink-800 p-0 text-ink-100 backdrop:bg-ink-950/70">
        <div class="p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-ink-50">What do these terms mean?</h3>
                    <p class="mt-1 text-sm text-ink-300">For explaining the store fields during a demo — plain language for a client, technical for an engineer.</p>
                </div>
                <button type="button" data-glossary-close="{{ $modalId }}"
                        class="shrink-0 rounded-lg p-1.5 text-ink-400 transition hover:bg-ink-750 hover:text-ink-100">
                    ✕
                </button>
            </div>

            <div class="mt-4 table-shell">
                <table class="table-base">
                    <thead class="table-head">
                        <tr>
                            <th>Term</th>
                            <th>Plain language</th>
                            <th>Technical</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($terms as $term)
                            <tr class="align-top">
                                <td class="whitespace-nowrap font-medium text-ink-50">{{ $term['label'] }}</td>
                                <td class="max-w-xs text-ink-200">{{ $term['plain'] }}</td>
                                <td class="max-w-xs font-mono text-xs leading-relaxed text-ink-300">{{ $term['technical'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </dialog>
</div>

<script>
    (function () {
        var dialog = document.getElementById(@json($modalId));
        if (!dialog) return;

        document.querySelectorAll('[data-glossary-open="' + @json($modalId) + '"]').forEach(function (btn) {
            btn.addEventListener('click', function () { dialog.showModal(); });
        });

        dialog.querySelectorAll('[data-glossary-close="' + @json($modalId) + '"]').forEach(function (btn) {
            btn.addEventListener('click', function () { dialog.close(); });
        });

        dialog.addEventListener('click', function (event) {
            if (event.target === dialog) dialog.close();
        });
    })();
</script>
