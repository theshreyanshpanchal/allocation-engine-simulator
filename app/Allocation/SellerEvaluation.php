<?php

namespace App\Allocation;

/**
 * One store's standing in a single allocation evaluation: its inputs, whether
 * it can participate, why not if it can't, and the target share it would carry.
 *
 * Excluded stores are kept (participating = false, exclusionReason set) so the
 * audit trail and UI can show the full picture.
 */
final class SellerEvaluation
{
    public function __construct(
        public readonly int $storeId,
        public readonly string $storeCode,
        public readonly string $storeName,
        public readonly float $price,
        public readonly float $score,
        public readonly float $distanceKm,
        public readonly bool $storeActive,
        public readonly bool $stockAvailable,
        public readonly bool $serviceable,
        /** Passed every pre-price-guard check (active + in stock + serviceable). */
        public readonly bool $eligible,
        /** Within the price band (only meaningful when $eligible). */
        public readonly bool $priceGuardEligible,
        /** In the allocation draw: $eligible && $priceGuardEligible. */
        public readonly bool $participating,
        public readonly ?string $exclusionReason,
        /** Fraction 0..1 of the opportunity this store should receive. */
        public readonly float $targetShare,
    ) {
    }

    public function targetSharePercent(): float
    {
        return round($this->targetShare * 100, 1);
    }
}
