<?php

namespace App\Allocation;

use App\Models\Product;

/**
 * Turns "this product at this tolerance" into a full {@see AllocationEvaluation}:
 * eligibility -> price guard -> score-weighted target shares.
 *
 * Target shares are always computed over the stores that survive the price
 * guard, never by trimming a precomputed 5/4/3 split (spec section 60).
 *
 * Pure: no persistence, no session, no winner selection. The stateful pick
 * lives in ticket 06.
 */
class AllocationEngine
{
    public function __construct(
        private readonly EligibilityService $eligibility = new EligibilityService(),
        private readonly PriceGuardService $priceGuard = new PriceGuardService(),
    ) {
    }

    public function evaluate(Product $product, float $tolerancePercent, ?string $buyerPostcode = null): AllocationEvaluation
    {
        $offers = $product->relationLoaded('offers')
            ? $product->offers
            : $product->offers()->get();
        $offers->loadMissing('store');

        $drafts = $this->eligibility->draftsFor($offers, $buyerPostcode);

        ['lowestPrice' => $lowest, 'priceCeiling' => $ceiling] =
            $this->priceGuard->apply($drafts, $tolerancePercent);

        $this->assignTargetShares($drafts);

        return new AllocationEvaluation(
            productId: $product->id,
            tolerancePercent: max(0.0, $tolerancePercent),
            lowestPrice: $lowest,
            priceCeiling: $ceiling,
            sellers: array_map(fn (SellerDraft $d) => $d->toEvaluation(), $drafts),
        );
    }

    /**
     * @param  list<SellerDraft>  $drafts
     */
    private function assignTargetShares(array $drafts): void
    {
        $participating = array_values(array_filter($drafts, fn (SellerDraft $d) => $d->participating()));

        foreach ($drafts as $draft) {
            $draft->targetShare = 0.0;
        }

        if ($participating === []) {
            return;
        }

        $totalScore = array_sum(array_map(fn (SellerDraft $d) => $d->score, $participating));

        // All-zero scores: fall back to an equal split (spec section 95).
        if ($totalScore <= 0.0) {
            $equal = 1.0 / count($participating);
            foreach ($participating as $draft) {
                $draft->targetShare = $equal;
            }

            return;
        }

        foreach ($participating as $draft) {
            $draft->targetShare = $draft->score / $totalScore;
        }
    }
}
