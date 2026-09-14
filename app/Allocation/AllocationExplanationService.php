<?php

namespace App\Allocation;

use App\Models\AllocationDecision;

/**
 * Turns an allocation — whether a live dashboard evaluation or a stored
 * decision — into a business and a technical explanation. Both are generated
 * from the same recorded inputs so the words never disagree with the numbers.
 */
class AllocationExplanationService
{
    public function forEvaluation(AllocationEvaluation $evaluation, ?SellerEvaluation $winner = null): AllocationExplanation
    {
        $facts = [];
        foreach ($evaluation->sellers as $seller) {
            $facts[] = [
                'id' => $seller->storeId,
                'code' => $seller->storeCode,
                'name' => $seller->storeName,
                'score' => $seller->score,
                'price' => $seller->price,
                'eligible' => $seller->eligible,
                'participating' => $seller->participating,
                'priceExcluded' => $seller->eligible && ! $seller->priceGuardEligible,
                'exclusionReason' => $seller->exclusionReason,
                'targetShare' => $seller->targetShare,
            ];
        }

        return $this->build(
            facts: $facts,
            tolerance: $evaluation->tolerancePercent,
            lowestPrice: $evaluation->lowestPrice,
            priceCeiling: $evaluation->priceCeiling,
            winnerId: $winner?->storeId,
            winnerName: $winner?->storeName,
            winnerlessReason: $evaluation->winnerlessReason(),
        );
    }

    public function forDecision(AllocationDecision $decision): AllocationExplanation
    {
        $decision->loadMissing(['sellers.store', 'winner']);

        $facts = $decision->sellers
            ->sortBy('store.code')
            ->map(fn ($s) => [
                'id' => $s->store_id,
                'code' => $s->store->code,
                'name' => $s->store->name,
                'score' => (float) $s->score,
                'price' => (float) $s->price,
                // Passed the pre-price-guard checks: either it made it through the
                // price guard, or it was specifically cut BY the price guard — both
                // imply it was eligible going in. Any other exclusion reason means
                // it never got that far.
                'eligible' => (bool) $s->price_guard_eligible || $s->exclusion_reason === ExclusionReason::PRICE_OUTSIDE_TOLERANCE,
                'participating' => (bool) $s->price_guard_eligible,
                'priceExcluded' => $s->exclusion_reason === ExclusionReason::PRICE_OUTSIDE_TOLERANCE,
                'exclusionReason' => $s->exclusion_reason,
                'targetShare' => (float) $s->target_share,
            ])
            ->values()
            ->all();

        return $this->build(
            facts: $facts,
            tolerance: (float) $decision->tolerance_percent,
            lowestPrice: $decision->lowest_price !== null ? (float) $decision->lowest_price : null,
            priceCeiling: $decision->price_ceiling !== null ? (float) $decision->price_ceiling : null,
            winnerId: $decision->winner_store_id,
            winnerName: $decision->winner?->name,
            winnerlessReason: $decision->winner_store_id === null ? ($decision->decision_reason ?: 'No eligible stores.') : null,
        );
    }

    /**
     * @param  list<array{id:int,code:string,name:string,score:float,price:float,participating:bool,priceExcluded:bool,exclusionReason:?string,targetShare:float}>  $facts
     */
    private function build(
        array $facts,
        float $tolerance,
        ?float $lowestPrice,
        ?float $priceCeiling,
        ?int $winnerId,
        ?string $winnerName,
        ?string $winnerlessReason,
    ): AllocationExplanation {
        $participating = array_values(array_filter($facts, fn ($f) => $f['participating']));
        $priceExcluded = array_values(array_filter($facts, fn ($f) => $f['priceExcluded']));
        $otherExcluded = array_values(array_filter(
            $facts,
            fn ($f) => ! $f['participating'] && ! $f['priceExcluded'],
        ));

        $tol = num($tolerance);
        $perSellerNotes = $this->perSellerNotes($facts, $tol, $lowestPrice);

        // ---- Plain-language explanation --------------------------------------------
        // No insider terms here (no "operational score", "price guard", "target share")
        // — this register is for a non-technical customer or client. The technical
        // register below carries the real numbers for anyone who wants them.
        if ($participating === []) {
            $business = $winnerlessReason ?? 'No store could take this order right now.';
        } else {
            $winner = $this->findById($facts, $winnerId) ?? $participating[0];

            if (count($participating) === 1) {
                $business = "{$winner['name']} got this customer because its price was close enough to the best price available"
                    .($lowestPrice !== null ? ' (around '.money($lowestPrice).')' : '')
                    .', and no other store qualified this time.';
            } else {
                $business = "{$winner['name']} got this customer. All of ".$this->joinNames($participating)
                    .' had prices close enough to the best price available, so instead of always picking the cheapest, '
                    .'we share customers between trusted stores over time — giving more to the ones with a stronger track record. '
                    ."On that basis, {$winner['name']} usually gets around ".pct($winner['targetShare']).' of customers like this.';
            }

            if ($priceExcluded !== []) {
                $business .= ' '.$this->joinNames($priceExcluded)
                    .(count($priceExcluded) === 1 ? " wasn't" : " weren't")
                    .' included this time because '
                    .(count($priceExcluded) === 1 ? 'its price was' : 'their prices were')
                    ." more than {$tol}% higher than the best price available"
                    .($lowestPrice !== null ? ' ('.money($lowestPrice).')' : '').'.';
            }

            if ($otherExcluded !== []) {
                $business .= ' '.$this->joinNames($otherExcluded)
                    ." couldn't take this order right now.";
            }
        }

        // ---- Technical explanation ---------------------------------------------
        $lines = [];
        $lines[] = ['label' => 'Eligibility', 'value' => $participating === []
            ? 'none'
            : implode(', ', array_column($participating, 'code'))];
        $lines[] = ['label' => 'Lowest price', 'value' => $lowestPrice !== null ? number_format($lowestPrice, 2) : '—'];
        $lines[] = ['label' => 'Tolerance', 'value' => "{$tol}%"];
        $lines[] = ['label' => 'Price ceiling', 'value' => $priceCeiling !== null ? number_format($priceCeiling, 2) : '—'];

        $lines[] = ['label' => 'Price guard', 'value' => ''];
        foreach ($facts as $f) {
            $verdict = $f['participating']
                ? 'pass'
                : ($f['priceExcluded'] ? 'excluded (price)' : 'excluded ('.strtolower($f['exclusionReason'] ?? 'ineligible').')');
            $lines[] = ['label' => "  {$f['code']}", 'value' => $verdict];
        }

        if ($participating !== []) {
            $totalScore = array_sum(array_column($participating, 'score'));
            $lines[] = ['label' => 'Scores', 'value' => collect($participating)
                ->map(fn ($f) => "{$f['code']} = {$this->scoreLabel($f['score'])}")
                ->implode(', ')];
            $lines[] = ['label' => 'Total score', 'value' => $this->scoreLabel($totalScore)];
            $lines[] = ['label' => 'Target weights', 'value' => ''];
            foreach ($participating as $f) {
                $lines[] = [
                    'label' => "  {$f['code']}",
                    'value' => $totalScore > 0
                        ? "{$this->scoreLabel($f['score'])} / {$this->scoreLabel($totalScore)} = ".pct($f['targetShare'])
                        : pct($f['targetShare']),
                ];
            }
        }

        $lines[] = ['label' => 'Selected', 'value' => $winnerName ?? 'no eligible store'];

        return new AllocationExplanation(
            business: $business,
            technicalLines: $lines,
            perSellerNotes: $perSellerNotes,
            sellers: $facts,
            tolerancePercent: $tolerance,
            lowestPrice: $lowestPrice,
            priceCeiling: $priceCeiling,
            winnerId: $winnerId,
            winnerName: $winnerName,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $facts
     * @return array<int, string>
     */
    private function perSellerNotes(array $facts, string $tol, ?float $lowestPrice): array
    {
        $notes = [];
        foreach ($facts as $f) {
            if ($f['participating']) {
                continue;
            }

            if ($f['priceExcluded']) {
                $notes[$f['id']] = "{$f['name']} wasn't chosen this time because its price was more than {$tol}% higher than the best price available"
                    .($lowestPrice !== null ? ' ('.money($lowestPrice).')' : '').'.';
            } else {
                $notes[$f['id']] = "{$f['name']} wasn't available for this order: "
                    .strtolower($f['exclusionReason'] ?? 'not available').'.';
            }
        }

        return $notes;
    }

    /** @param list<array<string,mixed>> $facts */
    private function findById(array $facts, ?int $id): ?array
    {
        if ($id === null) {
            return null;
        }

        foreach ($facts as $f) {
            if ($f['id'] === $id) {
                return $f;
            }
        }

        return null;
    }

    /** @param list<array<string,mixed>> $facts */
    private function joinNames(array $facts): string
    {
        $names = array_column($facts, 'name');

        if (count($names) <= 1) {
            return $names[0] ?? '';
        }

        $last = array_pop($names);

        return implode(', ', $names).' and '.$last;
    }

    private function scoreLabel(float $score): string
    {
        return num($score);
    }
}
