<?php

use App\Allocation\ExclusionReason;
use App\Allocation\ScenarioCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('produces eleven scenarios, none touching the real database', function () {
    $scenarios = app(ScenarioCatalog::class)->all();

    expect($scenarios)->toHaveCount(11);
    expect(\App\Models\Store::count())->toBe(0)
        ->and(\App\Models\Product::count())->toBe(0);
});

it('Scenario 1 reproduces the exact FRD worked example', function () {
    $scenario = app(ScenarioCatalog::class)->all()[0];

    expect(participatingSharesPercent($scenario->evaluation))->toBe(['A' => 41.7, 'B' => 33.3, 'C' => 25.0]);
});

it('Scenario 2 narrows to a single winner at 2% tolerance', function () {
    $scenario = app(ScenarioCatalog::class)->all()[1];

    expect(collect($scenario->evaluation->participating())->pluck('storeCode')->all())->toBe(['C'])
        ->and($scenario->resultLine())->toBe('Only C qualifies — takes 100%.');
});

it('Scenario 5 keeps every store when zero tolerance meets a price tie', function () {
    $scenario = app(ScenarioCatalog::class)->all()[4];

    expect(collect($scenario->evaluation->participating())->pluck('storeCode')->all())->toBe(['A', 'B', 'C'])
        ->and(participatingSharesPercent($scenario->evaluation))->toBe(['A' => 41.7, 'B' => 33.3, 'C' => 25.0]);
});

it('Scenario 6 excludes the inactive store with the right reason, before price', function () {
    $scenario = app(ScenarioCatalog::class)->all()[5];

    $a = collect($scenario->evaluation->sellers)->firstWhere('storeCode', 'A');
    expect($a->exclusionReason)->toBe(ExclusionReason::INACTIVE)
        ->and(participatingSharesPercent($scenario->evaluation))->toBe(['B' => 57.1, 'C' => 42.9]);
});

it('Scenario 7 excludes the out-of-stock store even though it has the best price and score', function () {
    $scenario = app(ScenarioCatalog::class)->all()[6];

    $a = collect($scenario->evaluation->sellers)->firstWhere('storeCode', 'A');
    expect($a->exclusionReason)->toBe(ExclusionReason::OUT_OF_STOCK)
        ->and(participatingSharesPercent($scenario->evaluation))->toBe(['B' => 57.1, 'C' => 42.9]);
});

it('Scenario 8 is winnerless when every store is out of stock', function () {
    $scenario = app(ScenarioCatalog::class)->all()[7];

    expect($scenario->evaluation->hasParticipatingSellers())->toBeFalse()
        ->and($scenario->evaluation->winnerlessReason())->toBe('All stores are out of stock for this product.');
});

it('Scenario 9 lets geography narrow the field to one store before price is checked', function () {
    $scenario = app(ScenarioCatalog::class)->all()[8];

    expect(collect($scenario->evaluation->participating())->pluck('storeCode')->all())->toBe(['C']);

    $a = collect($scenario->evaluation->sellers)->firstWhere('storeCode', 'A');
    expect($a->exclusionReason)->toBe(ExclusionReason::NOT_SERVICEABLE);
});

it('Scenario 10 is winnerless via geography, with a distinct reason from the stock case', function () {
    $scenario = app(ScenarioCatalog::class)->all()[9];

    expect($scenario->evaluation->hasParticipatingSellers())->toBeFalse()
        ->and($scenario->evaluation->winnerlessReason())->toBe('No store can serve this buyer.');
});

it('Scenario 11 splits evenly across tied scores regardless of price differences', function () {
    $scenario = app(ScenarioCatalog::class)->all()[10];

    foreach ($scenario->evaluation->participating() as $seller) {
        expect(round($seller->targetShare, 4))->toBe(0.3333);
    }
});

it('renders the Scenario Playbook page with every case and no leaked internals', function () {
    $response = $this->get('/scenarios')->assertOk();

    $response->assertSee('Scenario Playbook')
        ->assertSee('Baseline — the FRD worked example')
        ->assertSee('Equal scores — a pure even split')
        ->assertSee('FR-GEO-003')
        ->assertDontSee('Whoops');

    $html = $response->getContent();
    expect($html)->not->toContain('ScenarioCatalog')
        ->and($html)->not->toContain('EligibilityService')
        ->and($html)->not->toContain('App\\Allocation');
});
