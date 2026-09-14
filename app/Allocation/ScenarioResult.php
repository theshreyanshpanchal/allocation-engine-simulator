<?php

namespace App\Allocation;

/**
 * A {@see ScenarioDefinition} after being run through the real engine: the
 * inputs as given, plus the evaluation and explanation the engine actually
 * produced. Nothing here is hand-typed — if the engine's behaviour ever
 * changes, these results change with it instead of quietly drifting stale.
 */
final class ScenarioResult
{
    public function __construct(
        public readonly ScenarioDefinition $definition,
        public readonly AllocationEvaluation $evaluation,
        public readonly AllocationExplanation $explanation,
    ) {
    }

    public function title(): string
    {
        return $this->definition->title;
    }

    public function summary(): string
    {
        return $this->definition->summary;
    }

    /** @return list<string> */
    public function frIds(): array
    {
        return $this->definition->frIds;
    }

    /** One-line statement of what happened, for the card's headline result. */
    public function resultLine(): string
    {
        $participating = $this->evaluation->participating();

        if ($participating === []) {
            return $this->evaluation->winnerlessReason() ?? 'No eligible stores.';
        }

        if (count($participating) === 1) {
            return "Only {$participating[0]->storeCode} qualifies — takes 100%.";
        }

        $parts = array_map(
            fn (SellerEvaluation $s) => "{$s->storeCode} ".pct($s->targetShare),
            $participating,
        );

        return implode(' · ', $parts);
    }
}
