@props(['explanation', 'title' => 'Why did this store win?'])

@php /** @var \App\Allocation\AllocationExplanation $explanation */ @endphp

<div class="card p-5">
    <h2 class="section-title">{{ $title }}</h2>

    <p class="mt-2 text-sm leading-relaxed text-ink-200">{{ $explanation->business }}</p>

    <details class="mt-4 group">
        <summary class="flex cursor-pointer items-center gap-1 text-sm font-medium text-brand-400 transition hover:text-brand-300">
            <svg class="h-3.5 w-3.5 transition group-open:rotate-90" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
            </svg>
            Technical explanation
        </summary>

        <div class="mt-3 rounded-xl border border-ink-650 bg-ink-800 p-3">
            <x-allocation-flow :explanation="$explanation" />
        </div>

        <details class="mt-2">
            <summary class="cursor-pointer text-xs font-medium text-ink-400 transition hover:text-ink-200">
                View as plain text
            </summary>
            <pre class="mt-2 overflow-x-auto rounded-xl border border-ink-650 bg-ink-750 p-3 font-mono text-xs leading-relaxed text-ink-200">{{ $explanation->technicalText() }}</pre>
        </details>
    </details>
</div>
