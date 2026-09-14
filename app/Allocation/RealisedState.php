<?php

namespace App\Allocation;

/**
 * How many opportunities each store has already received in a given context.
 * Immutable — {@see withAllocation()} returns a new instance so a simulation
 * can fold over purchases without shared mutable state.
 */
final class RealisedState
{
    /**
     * @param  array<int, int>  $counts  store id => allocations received
     */
    public function __construct(
        public readonly array $counts = [],
        public readonly int $total = 0,
    ) {
    }

    public function countFor(int $storeId): int
    {
        return $this->counts[$storeId] ?? 0;
    }

    public function shareFor(int $storeId): float
    {
        return $this->total > 0 ? $this->countFor($storeId) / $this->total : 0.0;
    }

    public function withAllocation(int $storeId): self
    {
        $counts = $this->counts;
        $counts[$storeId] = ($counts[$storeId] ?? 0) + 1;

        return new self($counts, $this->total + 1);
    }
}
