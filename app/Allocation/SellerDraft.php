<?php

namespace App\Allocation;

/**
 * Mutable working row used while the engine assembles an evaluation. The
 * eligibility and price-guard steps fill in their fields; the engine then
 * freezes each draft into an immutable {@see SellerEvaluation}.
 */
final class SellerDraft
{
    public bool $storeActive = false;

    public bool $stockAvailable = false;

    public bool $serviceable = false;

    public bool $eligible = false;

    public bool $priceGuardEligible = false;

    public ?string $exclusionReason = null;

    public float $targetShare = 0.0;

    public function __construct(
        public readonly int $storeId,
        public readonly string $storeCode,
        public readonly string $storeName,
        public readonly float $price,
        public readonly float $score,
        public readonly float $distanceKm,
    ) {
    }

    public function participating(): bool
    {
        return $this->eligible && $this->priceGuardEligible;
    }

    public function toEvaluation(): SellerEvaluation
    {
        return new SellerEvaluation(
            storeId: $this->storeId,
            storeCode: $this->storeCode,
            storeName: $this->storeName,
            price: $this->price,
            score: $this->score,
            distanceKm: $this->distanceKm,
            storeActive: $this->storeActive,
            stockAvailable: $this->stockAvailable,
            serviceable: $this->serviceable,
            eligible: $this->eligible,
            priceGuardEligible: $this->priceGuardEligible,
            participating: $this->participating(),
            exclusionReason: $this->participating() ? null : $this->exclusionReason,
            targetShare: $this->targetShare,
        );
    }
}
