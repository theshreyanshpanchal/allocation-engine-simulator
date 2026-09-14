<?php

namespace App\Allocation;

/**
 * Resolves the "current featured store" for the dashboard and keeps it stable
 * within a session (spec section 32). The pick is made once per
 * product + tolerance + surviving-seller set and cached in the session, so a
 * reload or a "Re-evaluate" returns the same store. Changing the product or
 * tolerance (which can change the survivor set) yields a fresh pick.
 */
class FeaturedStoreResolver
{
    public function __construct(
        private readonly DeficitAllocationStrategy $strategy,
        private readonly AllocationStateStore $stateStore,
    ) {
    }

    public function sessionKey(AllocationEvaluation $evaluation): string
    {
        $tolerance = rtrim(rtrim(number_format($evaluation->tolerancePercent, 2, '.', ''), '0'), '.');

        return implode(':', [
            'featured',
            $evaluation->productId,
            $tolerance === '' ? '0' : $tolerance,
            $evaluation->eligibleContextKey(),
        ]);
    }

    public function resolve(AllocationEvaluation $evaluation): ?SellerEvaluation
    {
        if (! $evaluation->hasParticipatingSellers()) {
            return null;
        }

        $key = $this->sessionKey($evaluation);
        $storedId = session()->get($key);

        if ($storedId !== null) {
            $seller = $evaluation->sellerFor((int) $storedId);
            if ($seller !== null && $seller->participating) {
                return $seller;
            }
        }

        $picked = $this->pick($evaluation);
        session()->put($key, $picked->storeId);

        return $picked;
    }

    private function pick(AllocationEvaluation $evaluation): SellerEvaluation
    {
        $participantIds = array_map(
            fn (SellerEvaluation $s) => $s->storeId,
            $evaluation->participating(),
        );

        $state = $this->stateStore->realisedState(
            $evaluation->productId,
            $evaluation->eligibleContextKey(),
            $participantIds,
        );

        return $this->strategy->select($evaluation, $state);
    }
}
