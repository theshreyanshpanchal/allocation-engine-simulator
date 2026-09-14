<?php

namespace App\Allocation;

/**
 * The price guard: given the eligible stores and a tolerance percentage,
 * find the lowest eligible price, derive the ceiling, and mark each eligible
 * store in or out of the band.
 *
 *   ceiling = lowest_price * (1 + tolerance / 100)
 *
 * Zero tolerance collapses the ceiling onto the lowest price, so only stores
 * at that price participate (ties at the lowest price all stay in).
 */
class PriceGuardService
{
    /** Half a cent — money is 2dp, so this absorbs float noise without changing outcomes. */
    public const EPSILON = 0.005;

    /**
     * Mutates the eligible drafts in place; returns the band that was applied.
     *
     * @param  list<SellerDraft>  $drafts
     * @return array{lowestPrice: float|null, priceCeiling: float|null}
     */
    public function apply(array $drafts, float $tolerancePercent): array
    {
        $tolerance = max(0.0, $tolerancePercent);

        $eligible = array_filter($drafts, fn (SellerDraft $d) => $d->eligible);

        if ($eligible === []) {
            return ['lowestPrice' => null, 'priceCeiling' => null];
        }

        $lowest = min(array_map(fn (SellerDraft $d) => $d->price, $eligible));
        $ceiling = round($lowest * (1 + $tolerance / 100), 2);

        foreach ($eligible as $draft) {
            if ($draft->price <= $ceiling + self::EPSILON) {
                $draft->priceGuardEligible = true;
                $draft->exclusionReason = null;
            } else {
                $draft->priceGuardEligible = false;
                $draft->exclusionReason = ExclusionReason::PRICE_OUTSIDE_TOLERANCE;
            }
        }

        return ['lowestPrice' => $lowest, 'priceCeiling' => $ceiling];
    }
}
