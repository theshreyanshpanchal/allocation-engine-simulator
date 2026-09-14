<?php

namespace App\Allocation;

/**
 * The result of evaluating one product at one price tolerance: every store's
 * standing, the price band that was applied, and helpers for the pieces the
 * UI, the stateful strategy and the audit recorder all need.
 */
final class AllocationEvaluation
{
    /**
     * @param  list<SellerEvaluation>  $sellers  every store that offers the product, in store-code order
     */
    public function __construct(
        public readonly int $productId,
        public readonly float $tolerancePercent,
        public readonly ?float $lowestPrice,
        public readonly ?float $priceCeiling,
        public readonly array $sellers,
    ) {
    }

    /** @return list<SellerEvaluation> */
    public function participating(): array
    {
        return array_values(array_filter($this->sellers, fn (SellerEvaluation $s) => $s->participating));
    }

    /** @return list<SellerEvaluation> */
    public function excluded(): array
    {
        return array_values(array_filter($this->sellers, fn (SellerEvaluation $s) => ! $s->participating));
    }

    /** @return list<SellerEvaluation> stores that passed eligibility but were cut by the price guard */
    public function excludedByPriceGuard(): array
    {
        return array_values(array_filter(
            $this->sellers,
            fn (SellerEvaluation $s) => $s->eligible && ! $s->priceGuardEligible,
        ));
    }

    public function hasParticipatingSellers(): bool
    {
        return $this->participating() !== [];
    }

    public function isSingleParticipant(): bool
    {
        return count($this->participating()) === 1;
    }

    public function sellerFor(int $storeId): ?SellerEvaluation
    {
        foreach ($this->sellers as $seller) {
            if ($seller->storeId === $storeId) {
                return $seller;
            }
        }

        return null;
    }

    public function targetShareFor(int $storeId): float
    {
        return $this->sellerFor($storeId)?->targetShare ?? 0.0;
    }

    /**
     * A stable key for the participating-store set, used to scope allocation
     * state (ticket 06). Same set of stores -> same key, order-independent.
     */
    public function eligibleContextKey(): string
    {
        $codes = array_map(fn (SellerEvaluation $s) => $s->storeCode, $this->participating());
        sort($codes);

        return $codes === [] ? 'none' : implode('|', $codes);
    }

    /** Human-readable reason there is no winner, or null when there is one. */
    public function winnerlessReason(): ?string
    {
        if ($this->hasParticipatingSellers()) {
            return null;
        }

        if ($this->sellers === []) {
            return 'This product has no store offers.';
        }

        if (collect($this->sellers)->every(fn (SellerEvaluation $s) => ! $s->stockAvailable)) {
            return 'All stores are out of stock for this product.';
        }

        if (collect($this->sellers)->every(fn (SellerEvaluation $s) => ! $s->serviceable)) {
            return 'No store can serve this buyer.';
        }

        return 'No eligible stores are available.';
    }
}
