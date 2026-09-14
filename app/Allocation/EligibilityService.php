<?php

namespace App\Allocation;

use App\Models\ProductOffer;

/**
 * Decides whether a store can serve the buyer at all, before price is
 * considered. Checks, in order: store active, product in stock, store
 * serviceable for the buyer (spec section 24).
 *
 * "Serviceable" is two checks folded into one: the store's own admin toggle
 * (open for business in this area at all) AND, when a buyer postcode and a
 * service area are both present, whether that postcode falls in the store's
 * coverage (see {@see \App\Models\Store::coversPostcode()}).
 */
class EligibilityService
{
    public function draftFor(ProductOffer $offer, ?string $buyerPostcode = null): SellerDraft
    {
        $store = $offer->store;

        $draft = new SellerDraft(
            storeId: $store->id,
            storeCode: $store->code,
            storeName: $store->name,
            price: (float) $offer->price,
            score: (float) $store->operational_score,
            distanceKm: (float) $store->distance_km,
        );

        $draft->storeActive = $store->isActive();
        $draft->stockAvailable = $offer->inStock();
        $draft->serviceable = (bool) $store->serviceable && $store->coversPostcode($buyerPostcode);

        $this->markEligibility($draft);

        return $draft;
    }

    /**
     * @param  iterable<ProductOffer>  $offers
     * @return list<SellerDraft>
     */
    public function draftsFor(iterable $offers, ?string $buyerPostcode = null): array
    {
        $drafts = [];

        foreach ($offers as $offer) {
            $drafts[] = $this->draftFor($offer, $buyerPostcode);
        }

        usort($drafts, fn (SellerDraft $a, SellerDraft $b) => strcmp($a->storeCode, $b->storeCode));

        return $drafts;
    }

    private function markEligibility(SellerDraft $draft): void
    {
        if (! $draft->storeActive) {
            $draft->eligible = false;
            $draft->exclusionReason = ExclusionReason::INACTIVE;

            return;
        }

        if (! $draft->stockAvailable) {
            $draft->eligible = false;
            $draft->exclusionReason = ExclusionReason::OUT_OF_STOCK;

            return;
        }

        if (! $draft->serviceable) {
            $draft->eligible = false;
            $draft->exclusionReason = ExclusionReason::NOT_SERVICEABLE;

            return;
        }

        $draft->eligible = true;
        $draft->exclusionReason = null;
    }
}
