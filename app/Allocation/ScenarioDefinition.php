<?php

namespace App\Allocation;

/**
 * One hand-picked case for the Scenario Playbook: a named, explainable
 * combination of tolerance + buyer postcode + store inputs, chosen to isolate
 * one mechanism of the engine at a time (eligibility ordering, the price
 * guard's three regimes, geography, score edge cases).
 *
 * @property-read list<array{code:string,name:string,score:float,price:float,distance?:float,active?:bool,stock?:int,serviceable?:bool,postcodePrefixes?:?string}> $stores
 */
final class ScenarioDefinition
{
    /**
     * @param  list<string>  $frIds  requirement IDs this case demonstrates, for the interview walkthrough
     * @param  list<array{code:string,name:string,score:float,price:float,distance?:float,active?:bool,stock?:int,serviceable?:bool,postcodePrefixes?:?string}>  $stores
     */
    public function __construct(
        public readonly string $title,
        public readonly string $summary,
        public readonly array $frIds,
        public readonly float $tolerancePercent,
        public readonly array $stores,
        public readonly ?string $buyerPostcode = null,
    ) {
    }
}
