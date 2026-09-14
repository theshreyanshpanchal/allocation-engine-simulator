<?php

namespace App\Allocation;

/**
 * Aggregate result of a completed simulation run: per-store target vs realised
 * shares plus the price band that was in force. Rebuildable from the persisted
 * {@see \App\Models\AllocationRun} so it survives Livewire round-trips.
 */
final class SimulationOutcome
{
    /**
     * @param  list<array{storeId:int,storeCode:string,storeName:string,score:float,targetShare:float,realisedShare:float,differencePoints:float,allocations:int}>  $rows
     */
    public function __construct(
        public readonly int $runId,
        public readonly int $purchaseCount,
        public readonly float $tolerancePercent,
        public readonly ?float $lowestPrice,
        public readonly ?float $priceCeiling,
        public readonly array $rows,
        /** store id => times excluded by the price guard across the run */
        public readonly array $priceGuardExclusions = [],
    ) {
    }
}
