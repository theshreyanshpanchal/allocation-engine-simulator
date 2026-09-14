<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Project Atlas — Allocation Engine Simulator' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full text-ink-100 antialiased">
    <div class="min-h-full">
        <header class="sticky top-0 z-10 border-b border-ink-650 bg-ink-900/85 backdrop-blur">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-400 text-sm font-bold text-white">
                            A
                        </span>
                        <span class="flex flex-col leading-none">
                            <span class="text-sm font-semibold tracking-wide text-ink-50">Project Atlas</span>
                            <span class="hidden text-xs text-ink-400 sm:inline">Allocation Engine Simulator</span>
                        </span>
                        <span class="ml-2 hidden items-center gap-1.5 rounded-full border border-ink-600 bg-ink-800 px-2.5 py-1 font-mono text-[0.65rem] uppercase tracking-widest text-emerald-700 md:inline-flex">
                            <span class="live-dot"></span> Live
                        </span>
                    </a>
                    <nav class="flex items-center gap-1">
                        @php
                            $items = [
                                ['route' => 'dashboard', 'pattern' => 'dashboard', 'label' => 'Dashboard'],
                                ['route' => 'stores.index', 'pattern' => 'stores.*', 'label' => 'Stores'],
                                ['route' => 'products.index', 'pattern' => 'products.*', 'label' => 'Products'],
                                ['route' => 'allocations.index', 'pattern' => 'allocations.*|simulations.*', 'label' => 'Allocation Logs'],
                                ['route' => 'scenarios.index', 'pattern' => 'scenarios.*', 'label' => 'Scenario Playbook'],
                            ];
                        @endphp
                        @foreach ($items as $item)
                            @php $active = request()->routeIs(...explode('|', $item['pattern'])); @endphp
                            <a href="{{ route($item['route']) }}"
                               class="{{ $active ? 'nav-link-active' : 'nav-link-idle' }}"
                               @if ($active) aria-current="page" @endif>
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </nav>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>
    </div>
</body>
</html>
