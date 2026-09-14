<?php

use App\Allocation\AllocationEngine;
use App\Allocation\ExclusionReason;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->engine = new AllocationEngine();
});

it('Test 1 — score allocation produces 41.7 / 33.3 / 25 for 5/4/3', function () {
    $evaluation = $this->engine->evaluate(makeBrakeDiscScenario(), 10);

    expect(participatingSharesPercent($evaluation))->toBe(['A' => 41.7, 'B' => 33.3, 'C' => 25.0]);
});

it('Test 2 — at 10% tolerance all three stores are inside the price guard', function () {
    $evaluation = $this->engine->evaluate(makeBrakeDiscScenario(), 10);

    expect($evaluation->lowestPrice)->toBe(470.0)
        ->and($evaluation->priceCeiling)->toBe(517.0)
        ->and(collect($evaluation->participating())->pluck('storeCode')->all())->toBe(['A', 'B', 'C']);
});

it('Test 3 — a narrow 2% tolerance leaves only Store C', function () {
    $evaluation = $this->engine->evaluate(makeBrakeDiscScenario(), 2);

    expect($evaluation->priceCeiling)->toBe(479.4)
        ->and(collect($evaluation->participating())->pluck('storeCode')->all())->toBe(['C'])
        ->and($evaluation->targetShareFor($evaluation->sellerFor(
            Store::where('code', 'C')->value('id')
        )->storeId))->toBe(1.0);

    $a = $evaluation->sellerFor(Store::where('code', 'A')->value('id'));
    $b = $evaluation->sellerFor(Store::where('code', 'B')->value('id'));
    expect($a->exclusionReason)->toBe(ExclusionReason::PRICE_OUTSIDE_TOLERANCE)
        ->and($b->exclusionReason)->toBe(ExclusionReason::PRICE_OUTSIDE_TOLERANCE);
});

it('Test 4 — two survivors (B, C) re-normalise to 57.1 / 42.9', function () {
    // 2.5% band on 470 -> 481.75: A (500) out, B (480) and C (470) in.
    $evaluation = $this->engine->evaluate(makeBrakeDiscScenario(), 2.5);

    expect($evaluation->priceCeiling)->toBe(481.75)
        ->and(participatingSharesPercent($evaluation))->toBe(['B' => 57.1, 'C' => 42.9]);
});

it('Test 5 — zero tolerance lets only the cheapest store participate', function () {
    $evaluation = $this->engine->evaluate(makeBrakeDiscScenario(), 0);

    expect($evaluation->priceCeiling)->toBe(470.0)
        ->and(collect($evaluation->participating())->pluck('storeCode')->all())->toBe(['C'])
        ->and($evaluation->isSingleParticipant())->toBeTrue();
});

it('zero tolerance keeps every store when they all share the lowest price', function () {
    $product = makeScenario([
        'A' => ['score' => 5, 'price' => 100],
        'B' => ['score' => 4, 'price' => 100],
        'C' => ['score' => 3, 'price' => 100],
    ]);

    $evaluation = $this->engine->evaluate($product, 0);

    expect(collect($evaluation->participating())->pluck('storeCode')->all())->toBe(['A', 'B', 'C'])
        ->and(participatingSharesPercent($evaluation))->toBe(['A' => 41.7, 'B' => 33.3, 'C' => 25.0]);
});

it('one eligible store gets 100% regardless of its score', function () {
    $product = makeScenario([
        'A' => ['score' => 5, 'price' => 500],
        'B' => ['score' => 4, 'price' => 800],
        'C' => ['score' => 3, 'price' => 470],
    ]);

    // 1% band on 470 -> 474.70: only C survives, and C has the lowest score.
    $evaluation = $this->engine->evaluate($product, 1);

    expect(participatingSharesPercent($evaluation))->toBe(['C' => 100.0]);
});

it('excludes an inactive store before price is considered', function () {
    $product = makeScenario([
        'A' => ['score' => 5, 'price' => 500, 'active' => false],
        'B' => ['score' => 4, 'price' => 480],
        'C' => ['score' => 3, 'price' => 470],
    ]);

    $evaluation = $this->engine->evaluate($product, 10);

    $a = collect($evaluation->sellers)->firstWhere('storeCode', 'A');
    expect($a->participating)->toBeFalse()
        ->and($a->exclusionReason)->toBe(ExclusionReason::INACTIVE)
        ->and(participatingSharesPercent($evaluation))->toBe(['B' => 57.1, 'C' => 42.9]);
});

it('excludes an out-of-stock store with the stock reason', function () {
    $product = makeScenario([
        'A' => ['score' => 5, 'price' => 500, 'stock' => 0],
        'B' => ['score' => 4, 'price' => 480],
        'C' => ['score' => 3, 'price' => 470],
    ]);

    $evaluation = $this->engine->evaluate($product, 10);

    expect(collect($evaluation->sellers)->firstWhere('storeCode', 'A')->exclusionReason)
        ->toBe(ExclusionReason::OUT_OF_STOCK);
});

it('returns a winnerless evaluation when every store is out of stock', function () {
    $product = makeScenario([
        'A' => ['score' => 5, 'price' => 500, 'stock' => 0],
        'B' => ['score' => 4, 'price' => 480, 'stock' => 0],
        'C' => ['score' => 3, 'price' => 470, 'stock' => 0],
    ]);

    $evaluation = $this->engine->evaluate($product, 10);

    expect($evaluation->hasParticipatingSellers())->toBeFalse()
        ->and($evaluation->winnerlessReason())->toBe('All stores are out of stock for this product.')
        ->and($evaluation->lowestPrice)->toBeNull();
});

it('gives equal shares to equal scores', function () {
    $product = makeScenario([
        'A' => ['score' => 4, 'price' => 500],
        'B' => ['score' => 4, 'price' => 480],
        'C' => ['score' => 4, 'price' => 470],
    ]);

    $evaluation = $this->engine->evaluate($product, 10);

    foreach ($evaluation->participating() as $seller) {
        expect(round($seller->targetShare, 4))->toBe(0.3333);
    }
});

it('keeps excluded stores in the result for the audit trail', function () {
    $evaluation = $this->engine->evaluate(makeBrakeDiscScenario(), 2);

    expect($evaluation->sellers)->toHaveCount(3)
        ->and($evaluation->excluded())->toHaveCount(2)
        ->and($evaluation->excludedByPriceGuard())->toHaveCount(2);
});

it('builds a stable, order-independent eligible-context key', function () {
    $product = makeBrakeDiscScenario();

    expect($this->engine->evaluate($product, 10)->eligibleContextKey())->toBe('A|B|C')
        ->and($this->engine->evaluate($product, 2)->eligibleContextKey())->toBe('C');
});
