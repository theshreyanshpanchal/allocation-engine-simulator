<?php

use App\Allocation\AllocationEngine;
use App\Allocation\DeficitAllocationStrategy;
use App\Allocation\RealisedState;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->engine = new AllocationEngine();
    $this->strategy = new DeficitAllocationStrategy();
});

it('picks the highest-target store first when nothing has been allocated yet', function () {
    $evaluation = $this->engine->evaluate(makeBrakeDiscScenario(), 10);

    $pick = $this->strategy->select($evaluation, new RealisedState());

    expect($pick->storeCode)->toBe('A');
});

it('is deterministic — the same evaluation and state always yield the same pick', function () {
    $evaluation = $this->engine->evaluate(makeBrakeDiscScenario(), 10);
    $state = new RealisedState();

    $picks = collect(range(1, 20))->map(fn () => $this->strategy->select($evaluation, $state)->storeCode);

    expect($picks->unique()->all())->toBe(['A']);
});

it('favours the store furthest behind its target once one store is ahead', function () {
    $evaluation = $this->engine->evaluate(makeBrakeDiscScenario(), 10);

    // Store A already holds 100% of a tiny history — it is well ahead of 41.7%.
    $aId = $evaluation->sellerFor(App\Models\Store::where('code', 'A')->value('id'))->storeId;
    $state = new RealisedState([$aId => 3], 3);

    expect($this->strategy->select($evaluation, $state)->storeCode)->not->toBe('A');
});

it('breaks ties by score then store code', function () {
    // Equal price, equal score -> equal target -> equal deficit at zero state.
    $product = makeScenario([
        'A' => ['score' => 4, 'price' => 100],
        'B' => ['score' => 4, 'price' => 100],
        'C' => ['score' => 4, 'price' => 100],
    ]);
    $evaluation = $this->engine->evaluate($product, 10);

    expect($this->strategy->select($evaluation, new RealisedState())->storeCode)->toBe('A');
});

it('converges toward the target distribution over many purchases', function () {
    $evaluation = $this->engine->evaluate(makeBrakeDiscScenario(), 10);

    $state = new RealisedState();
    for ($i = 0; $i < 1200; $i++) {
        $state = $state->withAllocation($this->strategy->select($evaluation, $state)->storeId);
    }

    foreach ($evaluation->participating() as $seller) {
        expect(abs($state->shareFor($seller->storeId) - $seller->targetShare))
            ->toBeLessThan(0.01);
    }
});

it('RealisedState reports shares and folds allocations immutably', function () {
    $state = new RealisedState();
    $next = $state->withAllocation(7)->withAllocation(7)->withAllocation(9);

    expect($state->total)->toBe(0)
        ->and($next->total)->toBe(3)
        ->and($next->shareFor(7))->toBe(2 / 3)
        ->and($next->shareFor(9))->toBe(1 / 3);
});
