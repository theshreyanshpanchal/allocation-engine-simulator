<?php

namespace App\Allocation;

use App\Models\AllocationState;

/**
 * Reads and updates the persisted realised-allocation counters, scoped by
 * product and by eligible-seller context (spec section 56). One row per
 * (product, context, store).
 */
class AllocationStateStore
{
    /**
     * @param  list<int>  $storeIds
     */
    public function realisedState(int $productId, string $contextKey, array $storeIds): RealisedState
    {
        $rows = AllocationState::query()
            ->where('product_id', $productId)
            ->where('eligible_context', $contextKey)
            ->whereIn('store_id', $storeIds)
            ->get();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row->store_id] = (int) $row->allocated_count;
        }

        // Each purchase has exactly one winner, so the total is the sum of wins.
        $total = array_sum($counts);

        return new RealisedState($counts, $total);
    }

    /**
     * Record one purchase: the winner gains an allocation; every participant's
     * total increments so realised shares stay on a common denominator.
     *
     * @param  array<int, float>  $targetShares  store id => target share (0..1)
     */
    public function recordAllocation(
        int $productId,
        string $contextKey,
        int $winnerStoreId,
        array $targetShares,
    ): void {
        foreach ($targetShares as $storeId => $targetShare) {
            $state = AllocationState::firstOrNew([
                'product_id' => $productId,
                'eligible_context' => $contextKey,
                'store_id' => $storeId,
            ]);

            $state->allocated_count = (int) $state->allocated_count + ($storeId === $winnerStoreId ? 1 : 0);
            $state->total_count = (int) $state->total_count + 1;
            $state->target_share = $targetShare;
            $state->realised_share = $state->total_count > 0
                ? $state->allocated_count / $state->total_count
                : 0.0;

            $state->save();
        }
    }

    /**
     * Fold a whole run's totals into the persisted state in one pass.
     *
     * @param  array<int, int>  $winCounts  store id => wins in this run
     * @param  array<int, float>  $targetShares  store id => target share (0..1)
     */
    public function addRunTotals(
        int $productId,
        string $contextKey,
        array $winCounts,
        int $purchases,
        array $targetShares,
    ): void {
        foreach ($targetShares as $storeId => $targetShare) {
            $state = AllocationState::firstOrNew([
                'product_id' => $productId,
                'eligible_context' => $contextKey,
                'store_id' => $storeId,
            ]);

            $state->allocated_count = (int) $state->allocated_count + (int) ($winCounts[$storeId] ?? 0);
            $state->total_count = (int) $state->total_count + $purchases;
            $state->target_share = $targetShare;
            $state->realised_share = $state->total_count > 0
                ? $state->allocated_count / $state->total_count
                : 0.0;

            $state->save();
        }
    }

    public function reset(int $productId, string $contextKey): void
    {
        AllocationState::query()
            ->where('product_id', $productId)
            ->where('eligible_context', $contextKey)
            ->delete();
    }
}
