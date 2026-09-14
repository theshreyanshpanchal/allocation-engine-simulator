@props(['tone' => 'neutral'])

@php
    $tones = [
        'neutral' => ['badge' => 'bg-ink-750 text-ink-300', 'dot' => 'bg-ink-400'],
        'success' => ['badge' => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20', 'dot' => 'bg-emerald-500'],
        'danger' => ['badge' => 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20', 'dot' => 'bg-rose-500'],
        'warning' => ['badge' => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20', 'dot' => 'bg-amber-500'],
        'info' => ['badge' => 'bg-brand-900 text-brand-300 ring-1 ring-inset ring-brand-400/25', 'dot' => 'bg-brand-400'],
    ];
    $style = $tones[$tone] ?? $tones['neutral'];
@endphp

<span {{ $attributes->class(['badge-base', $style['badge']]) }}>
    <span class="{{ $style['dot'] }} badge-dot"></span>{{ $slot }}
</span>
