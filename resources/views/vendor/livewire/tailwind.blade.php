{{-- Overrides Livewire's own bundled pagination view (livewire::tailwind), which is a
     SEPARATE view from Laravel's pagination::tailwind — Livewire's WithPagination trait
     swaps in its own view while rendering, so the pagination::tailwind override alone
     does not reach Livewire-paginated components like AllocationLog\Index. Mirrors
     resources/views/vendor/pagination/tailwind.blade.php but with wire:click instead
     of <a href>, since Livewire pagination is AJAX-driven. --}}
@php
    if (! isset($scrollTo)) {
        $scrollTo = 'body';
    }

    $scrollIntoViewJsSnippet = ($scrollTo !== false)
        ? <<<JS
           (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
        JS
        : '';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation"
             class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-ink-400">
                {!! __('Showing') !!}
                <span class="font-medium text-ink-100">{{ $paginator->firstItem() }}</span>
                {!! __('to') !!}
                <span class="font-medium text-ink-100">{{ $paginator->lastItem() }}</span>
                {!! __('of') !!}
                <span class="font-medium text-ink-100">{{ $paginator->total() }}</span>
                {!! __('results') !!}
            </p>

            <div class="flex flex-wrap items-center gap-1">
                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}"
                          class="inline-flex cursor-not-allowed items-center rounded-lg border border-ink-650 bg-ink-750 px-3 py-1.5 text-sm font-medium text-ink-400">
                        &larr;
                    </span>
                @else
                    <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')"
                            x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled"
                            aria-label="{{ __('pagination.previous') }}"
                            class="inline-flex items-center rounded-lg border border-ink-600 bg-ink-800 px-3 py-1.5 text-sm font-medium text-ink-200 transition hover:border-ink-500 hover:bg-ink-750">
                        &larr;
                    </button>
                @endif

                {{-- Page numbers / "..." separators --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span aria-disabled="true" class="inline-flex items-center px-2 text-sm text-ink-400">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page"
                                          class="inline-flex min-w-[2.25rem] items-center justify-center rounded-lg bg-brand-400 px-3 py-1.5 text-sm font-semibold text-white">
                                        {{ $page }}
                                    </span>
                                @else
                                    <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                            x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                            aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                                            class="inline-flex min-w-[2.25rem] items-center justify-center rounded-lg border border-ink-600 bg-ink-800 px-3 py-1.5 text-sm font-medium text-ink-200 transition hover:border-ink-500 hover:bg-ink-750">
                                        {{ $page }}
                                    </button>
                                @endif
                            </span>
                        @endforeach
                    @endif
                @endforeach

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')"
                            x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled"
                            aria-label="{{ __('pagination.next') }}"
                            class="inline-flex items-center rounded-lg border border-ink-600 bg-ink-800 px-3 py-1.5 text-sm font-medium text-ink-200 transition hover:border-ink-500 hover:bg-ink-750">
                        &rarr;
                    </button>
                @else
                    <span aria-disabled="true" aria-label="{{ __('pagination.next') }}"
                          class="inline-flex cursor-not-allowed items-center rounded-lg border border-ink-650 bg-ink-750 px-3 py-1.5 text-sm font-medium text-ink-400">
                        &rarr;
                    </span>
                @endif
            </div>
        </nav>
    @endif
</div>
