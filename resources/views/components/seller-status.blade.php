@props(['seller', 'featured' => false])

@php
    /** @var \App\Allocation\SellerEvaluation $seller */
    if ($featured) {
        $tone = 'info';
        $label = '★ Featured store';
    } elseif ($seller->participating) {
        $tone = 'success';
        $label = '✓ Eligible';
    } else {
        $tone = 'danger';
        $label = '✕ ' . $seller->exclusionReason;
    }
@endphp

<x-badge :tone="$tone" {{ $attributes }}>{{ $label }}</x-badge>
