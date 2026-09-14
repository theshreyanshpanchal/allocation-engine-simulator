<?php

use App\Allocation\AllocationEngine;
use App\Allocation\AllocationExplanationService;
use App\Allocation\DeficitAllocationStrategy;
use App\Allocation\RealisedState;
use App\Allocation\SimulationService;
use App\Livewire\Dashboard;
use App\Models\AllocationDecision;
use App\Models\Product;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->engine = new AllocationEngine();
    $this->explain = new AllocationExplanationService();
    $this->strategy = new DeficitAllocationStrategy();
});

it('builds every pipeline stage from the real evaluation, not fixed text', function () {
    $evaluation = $this->engine->evaluate(makeBrakeDiscScenario(), 10);
    $winner = $this->strategy->select($evaluation, new RealisedState());

    $explanation = $this->explain->forEvaluation($evaluation, $winner);

    // Stage 1: every seller, real prices.
    expect(collect($explanation->sellers)->pluck('code')->sort()->values()->all())->toBe(['A', 'B', 'C'])
        ->and(collect($explanation->sellers)->firstWhere('code', 'C')['price'])->toBe(470.0);

    // Stage 2: all three pass eligibility at 10% tolerance.
    expect($explanation->eligibleSellers())->toHaveCount(3);

    // Stage 3: all three are inside the price guard too.
    expect($explanation->participatingSellers())->toHaveCount(3);

    // Stage 4: real score-weighted math, not a canned number.
    expect($explanation->totalScore())->toBe(12.0);
    $a = collect($explanation->participatingSellers())->firstWhere('code', 'A');
    expect(round($a['targetShare'], 3))->toBe(0.417)
        ->and($explanation->winnerId)->toBe($a['id'])
        ->and($explanation->winnerName)->toBe('Store A');
});

it('drops a store out at the eligibility stage, not the price-guard stage', function () {
    $product = makeScenario([
        'A' => ['score' => 5, 'price' => 500, 'active' => false],
        'B' => ['score' => 4, 'price' => 480],
        'C' => ['score' => 3, 'price' => 470],
    ]);
    $evaluation = $this->engine->evaluate($product, 10);
    $winner = $this->strategy->select($evaluation, new RealisedState());

    $explanation = $this->explain->forEvaluation($evaluation, $winner);

    $a = collect($explanation->sellers)->firstWhere('code', 'A');
    expect($a['eligible'])->toBeFalse()
        ->and($a['exclusionReason'])->toBe(App\Allocation\ExclusionReason::INACTIVE)
        ->and($explanation->eligibleSellers())->toHaveCount(2)
        ->and(collect($explanation->eligibleSellers())->pluck('code')->sort()->values()->all())->toBe(['B', 'C']);
});

it('drops a store out at the price-guard stage, after passing eligibility', function () {
    $evaluation = $this->engine->evaluate(makeBrakeDiscScenario(), 2);
    $winner = $this->strategy->select($evaluation, new RealisedState());

    $explanation = $this->explain->forEvaluation($evaluation, $winner);

    // A and B pass eligibility (they're active/in stock/serviceable) but fail the price guard.
    expect($explanation->eligibleSellers())->toHaveCount(3)
        ->and($explanation->participatingSellers())->toHaveCount(1);

    $a = collect($explanation->sellers)->firstWhere('code', 'A');
    expect($a['eligible'])->toBeTrue()
        ->and($a['participating'])->toBeFalse()
        ->and($a['priceExcluded'])->toBeTrue();
});

it('renders the diagram stages, real numbers, and winner marker on the dashboard', function () {
    $this->seed(DemoDataSeeder::class);

    Livewire::test(Dashboard::class)
        ->assertSee('1. All sellers')
        ->assertSee('2. Eligibility check')
        ->assertSee('3. Price guard')
        ->assertSee('4. Score-weighted split')
        ->assertSee('5 ÷ 12 = 41.7%')
        ->assertSee('Selected: Store A')
        ->assertSee('View as plain text');
});

it('renders the diagram with real exclusion reasons on the decision detail page', function () {
    $this->seed(DemoDataSeeder::class);
    $disc = Product::where('sku', 'BRAKE-DISC-001')->firstOrFail();
    $run = app(SimulationService::class)->run($disc, 2, 5);
    $decision = AllocationDecision::where('allocation_run_id', $run->id)->firstOrFail();

    $this->get(route('allocations.show', $decision))
        ->assertOk()
        ->assertSee('1. All sellers')
        ->assertSee('4. Score-weighted split')
        ->assertSee('more than 2% above the lowest price')
        ->assertSee('Selected: Store C');
});

it('shows a clear empty state at every stage when nothing is eligible', function () {
    $product = makeScenario([
        'A' => ['score' => 5, 'price' => 500, 'stock' => 0],
        'B' => ['score' => 4, 'price' => 480, 'stock' => 0],
        'C' => ['score' => 3, 'price' => 470, 'stock' => 0],
    ]);
    $evaluation = $this->engine->evaluate($product, 10);
    $explanation = $this->explain->forEvaluation($evaluation, null);

    expect($explanation->eligibleSellers())->toBe([])
        ->and($explanation->participatingSellers())->toBe([])
        ->and($explanation->winnerName)->toBeNull();
});
