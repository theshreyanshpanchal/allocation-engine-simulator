<?php

namespace App\Allocation;

/**
 * State-aware, deterministic winner selection (spec section 61).
 *
 * For each participating store: deficit = target_share - realised_share.
 * The store furthest behind its target wins. Ties break by higher score,
 * then by store code — never randomly, so a run is reproducible from its
 * starting state.
 */
class DeficitAllocationStrategy
{
    public function select(AllocationEvaluation $evaluation, RealisedState $state): ?SellerEvaluation
    {
        $participating = $evaluation->participating();

        if ($participating === []) {
            return null;
        }

        usort($participating, function (SellerEvaluation $a, SellerEvaluation $b) use ($evaluation, $state) {
            $deficitA = $evaluation->targetShareFor($a->storeId) - $state->shareFor($a->storeId);
            $deficitB = $evaluation->targetShareFor($b->storeId) - $state->shareFor($b->storeId);

            // Larger deficit first; then higher score; then store code ascending.
            return [$deficitB, $b->score, $a->storeCode] <=> [$deficitA, $a->score, $b->storeCode];
        });

        return $participating[0];
    }

    public function deficitFor(AllocationEvaluation $evaluation, RealisedState $state, int $storeId): float
    {
        return $evaluation->targetShareFor($storeId) - $state->shareFor($storeId);
    }
}
