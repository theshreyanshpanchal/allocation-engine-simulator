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

function winnerFor($engine, $strategy, Product $product, float $tol)
{
    $evaluation = $engine->evaluate($product, $tol);

    return [$evaluation, $strategy->select($evaluation, new RealisedState())];
}

it('explains the default score-weighted scenario in both registers', function () {
    [$evaluation, $winner] = winnerFor($this->engine, $this->strategy, makeBrakeDiscScenario(), 10);

    $explanation = $this->explain->forEvaluation($evaluation, $winner);

    expect($explanation->business)
        ->toContain('Store A')
        ->toContain('track record')
        ->toContain('41.7%')
        ->not->toContain('operational score')
        ->not->toContain('price guard')
        ->not->toContain('target share');

    $tech = $explanation->technicalText();
    expect($tech)
        ->toContain('Eligibility: A, B, C')
        ->toContain('Lowest price: 470.00')
        ->toContain('Tolerance: 10%')
        ->toContain('Price ceiling: 517.00')
        ->toContain('Total score: 12')
        ->toContain('5 / 12 = 41.7%')
        ->toContain('Selected: Store A');
});

it('explains stores excluded by price in plain language (spec section 38)', function () {
    [$evaluation, $winner] = winnerFor($this->engine, $this->strategy, makeBrakeDiscScenario(), 2);

    $explanation = $this->explain->forEvaluation($evaluation, $winner);

    expect($explanation->business)
        ->toContain('Store C got this customer')
        ->toContain('no other store qualified');

    $notes = collect($explanation->perSellerNotes)->values();
    expect($notes)->toHaveCount(2);
    $notes->each(fn ($n) => expect($n)->toContain('more than 2% higher than the best price available'));
});

it('explains a store excluded before price is considered', function () {
    $product = makeScenario([
        'A' => ['score' => 5, 'price' => 500, 'active' => false],
        'B' => ['score' => 4, 'price' => 480],
        'C' => ['score' => 3, 'price' => 470],
    ]);
    [$evaluation, $winner] = winnerFor($this->engine, $this->strategy, $product, 10);

    $explanation = $this->explain->forEvaluation($evaluation, $winner);
    $aId = $evaluation->sellerFor(App\Models\Store::where('code', 'A')->value('id'))->storeId;

    expect($explanation->perSellerNotes[$aId])
        ->toContain("wasn't available for this order")
        ->toContain('store is inactive');
});

it('falls back to the winnerless reason when nothing is eligible', function () {
    $product = makeScenario([
        'A' => ['score' => 5, 'price' => 500, 'stock' => 0],
        'B' => ['score' => 4, 'price' => 480, 'stock' => 0],
        'C' => ['score' => 3, 'price' => 470, 'stock' => 0],
    ]);
    $evaluation = $this->engine->evaluate($product, 10);

    expect($this->explain->forEvaluation($evaluation, null)->business)
        ->toBe('All stores are out of stock for this product.');
});

it('builds a consistent explanation from a stored decision', function () {
    $this->seed(DemoDataSeeder::class);
    $disc = Product::where('sku', 'BRAKE-DISC-001')->firstOrFail();
    $run = app(SimulationService::class)->run($disc, 2, 10);

    $decision = AllocationDecision::where('allocation_run_id', $run->id)->firstOrFail();
    $explanation = $this->explain->forDecision($decision);

    expect($explanation->business)->toContain('Store C got this customer')
        ->and($explanation->technicalText())
        ->toContain('Selected: Store C')
        ->toContain('Tolerance: 2%');

    expect($explanation->perSellerNotes)->toHaveCount(2);
});

it('renders the "why did this store win?" panel on the dashboard', function () {
    $this->seed(DemoDataSeeder::class);

    Livewire::test(Dashboard::class)
        ->assertSee('Why did this store win?')
        ->assertSee('track record')
        ->assertSee('Technical explanation')
        ->assertSee('Selected: Store A');
});

it('renders both explanation registers on the decision detail page', function () {
    $this->seed(DemoDataSeeder::class);
    $disc = Product::where('sku', 'BRAKE-DISC-001')->firstOrFail();
    $run = app(SimulationService::class)->run($disc, 10, 10);
    $decision = AllocationDecision::where('allocation_run_id', $run->id)->firstOrFail();

    $this->get(route('allocations.show', $decision))
        ->assertOk()
        ->assertSee('Why this decision')
        ->assertSee('track record')
        ->assertSee('Technical explanation');
});
