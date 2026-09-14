<?php

if (! function_exists('num')) {
    /**
     * Format a number with up to $decimals places, trimming trailing zeros.
     * 5.0 -> "5", 2.5 -> "2.5", 41.666 -> "41.7".
     */
    function num(float|int|string|null $value, int $decimals = 1): string
    {
        if ($value === null) {
            return '—';
        }

        $formatted = number_format((float) $value, $decimals, '.', '');

        return str_contains($formatted, '.')
            ? rtrim(rtrim($formatted, '0'), '.')
            : $formatted;
    }
}

if (! function_exists('money')) {
    /** Format a value as demo currency: 470 -> "R$ 470.00". */
    function money(float|int|string|null $value): string
    {
        return $value === null
            ? '—'
            : 'R$ '.number_format((float) $value, 2, '.', ',');
    }
}

if (! function_exists('pct')) {
    /** Format a 0..1 fraction (or a raw percent) as a percentage string. */
    function pct(float|int|null $value, bool $isFraction = true, int $decimals = 1): string
    {
        if ($value === null) {
            return '—';
        }

        return number_format(($isFraction ? $value * 100 : $value), $decimals).'%';
    }
}
