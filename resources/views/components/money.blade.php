@props(['amount' => null])

<span {{ $attributes->class(['font-mono tabular-nums']) }}>{{ $amount === null ? '—' : 'R$ ' . number_format((float) $amount, 2, '.', ',') }}</span>
