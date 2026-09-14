<?php

namespace App\Allocation;

/**
 * The two registers of "why did this happen" for one allocation:
 * a plain-English paragraph and a structured technical breakdown
 * (spec sections 36–38, 66).
 *
 * The technical breakdown ships two shapes of the same facts: flat
 * label/value {@see $technicalLines} for a plain-text rendering, and the
 * per-store {@see $sellers} array (plus the tolerance/price/winner context)
 * for rendering the same facts as a decision-flow diagram — both built from
 * the same recorded inputs so neither can drift from the other.
 */
final class AllocationExplanation
{
    /**
     * @param  list<array{label: string, value: string}>  $technicalLines
     * @param  array<int, string>  $perSellerNotes  store id => one-line reason (excluded stores)
     * @param  list<array{id:int,code:string,name:string,score:float,price:float,eligible:bool,participating:bool,priceExcluded:bool,exclusionReason:?string,targetShare:float}>  $sellers
     */
    public function __construct(
        public readonly string $business,
        public readonly array $technicalLines,
        public readonly array $perSellerNotes = [],
        public readonly array $sellers = [],
        public readonly float $tolerancePercent = 0.0,
        public readonly ?float $lowestPrice = null,
        public readonly ?float $priceCeiling = null,
        public readonly ?int $winnerId = null,
        public readonly ?string $winnerName = null,
    ) {
    }

    public function technicalText(): string
    {
        return collect($this->technicalLines)
            ->map(fn (array $line) => $line['value'] === ''
                ? $line['label']
                : "{$line['label']}: {$line['value']}")
            ->implode("\n");
    }

    /** @return list<array<string,mixed>> every seller that passed the pre-price-guard checks */
    public function eligibleSellers(): array
    {
        return array_values(array_filter($this->sellers, fn ($s) => $s['eligible']));
    }

    /** @return list<array<string,mixed>> every seller inside the price guard (the score-weighted pool) */
    public function participatingSellers(): array
    {
        return array_values(array_filter($this->sellers, fn ($s) => $s['participating']));
    }

    public function totalScore(): float
    {
        return array_sum(array_column($this->participatingSellers(), 'score'));
    }
}
