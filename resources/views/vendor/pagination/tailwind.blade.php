{{-- Overrides Laravel's stock pagination::tailwind view (which ships with its own
     generic gray/blue + dark: classes, unrelated to this app's ink-/brand- design
     tokens) so pagination controls actually match the rest of the app. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
         class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-ink-400">
            {!! __('Showing') !!}
            @if ($paginator->firstItem())
                <span class="font-medium text-ink-100">{{ $paginator->firstItem() }}</span>
                {!! __('to') !!}
                <span class="font-medium text-ink-100">{{ $paginator->lastItem() }}</span>
            @else
                {{ $paginator->count() }}
            @endif
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
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}"
                   class="inline-flex items-center rounded-lg border border-ink-600 bg-ink-800 px-3 py-1.5 text-sm font-medium text-ink-200 transition hover:border-ink-500 hover:bg-ink-750">
                    &larr;
                </a>
            @endif

            {{-- Page numbers / "..." separators --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span aria-disabled="true" class="inline-flex items-center px-2 text-sm text-ink-400">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                  class="inline-flex min-w-[2.25rem] items-center justify-center rounded-lg bg-brand-400 px-3 py-1.5 text-sm font-semibold text-white">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                               class="inline-flex min-w-[2.25rem] items-center justify-center rounded-lg border border-ink-600 bg-ink-800 px-3 py-1.5 text-sm font-medium text-ink-200 transition hover:border-ink-500 hover:bg-ink-750">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}"
                   class="inline-flex items-center rounded-lg border border-ink-600 bg-ink-800 px-3 py-1.5 text-sm font-medium text-ink-200 transition hover:border-ink-500 hover:bg-ink-750">
                    &rarr;
                </a>
            @else
                <span aria-disabled="true" aria-label="{{ __('pagination.next') }}"
                      class="inline-flex cursor-not-allowed items-center rounded-lg border border-ink-650 bg-ink-750 px-3 py-1.5 text-sm font-medium text-ink-400">
                    &rarr;
                </span>
            @endif
        </div>
    </nav>
@endif
