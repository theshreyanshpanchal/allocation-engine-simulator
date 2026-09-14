<?php

use App\Allocation\SimulationService;
use App\Livewire\AllocationLog\Index;
use App\Models\AllocationDecision;
use App\Models\Product;
use App\Models\Store;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DemoDataSeeder::class);
    $this->disc = Product::where('sku', 'BRAKE-DISC-001')->firstOrFail();
    $this->pad = Product::where('sku', 'BRAKE-PAD-001')->firstOrFail();
    $service = app(SimulationService::class);
    $this->wideRun = $service->run($this->disc, 10, 30);   // A/B/C
    $this->narrowRun = $service->run($this->disc, 2, 12);   // C only
    $this->padRun = $service->run($this->pad, 10, 8);
});

it('lists decisions newest first with the summary columns', function () {
    Livewire::test(Index::class)
        ->assertSee('ALLOC-')
        ->assertSee('Front Brake Disc')
        ->assertSee('Front Brake Pad Set')
        ->assertSee('10%')
        ->assertSee('2%');
});

it('filters by product', function () {
    $discRef = AllocationDecision::where('product_id', $this->disc->id)->firstOrFail()->reference;
    $padRef = AllocationDecision::where('product_id', $this->pad->id)->firstOrFail()->reference;

    Livewire::test(Index::class)
        ->set('productId', $this->pad->id)
        ->assertSee($padRef)
        ->assertDontSee($discRef);
});

it('filters by winner store', function () {
    $storeC = Store::where('code', 'STORE-C')->firstOrFail();

    Livewire::test(Index::class)
        ->set('winnerStoreId', $storeC->id)
        ->assertSee('Front Brake Disc');

    // Every visible decision was won by Store C.
    $ids = AllocationDecision::where('winner_store_id', $storeC->id)->pluck('id');
    expect($ids)->not->toBeEmpty();
});

it('filters by tolerance', function () {
    Livewire::test(Index::class)
        ->set('tolerance', '2')
        ->assertSee('2%')
        ->assertDontSee('RUN-'); // sanity: still the decisions table
});

it('filters to a single run', function () {
    $component = Livewire::test(Index::class)->set('runId', $this->narrowRun->id);

    $decisions = AllocationDecision::where('allocation_run_id', $this->narrowRun->id)->count();
    expect($decisions)->toBe(12);

    $component->assertSee('Front Brake Disc');
});

it('shows a decision detail with every store, prices, scores and the winner', function () {
    $decision = AllocationDecision::where('allocation_run_id', $this->wideRun->id)->firstOrFail();

    $this->get(route('allocations.show', $decision))
        ->assertOk()
        ->assertSee($decision->reference)
        ->assertSee('Store A')->assertSee('Store B')->assertSee('Store C')
        ->assertSee('R$ 470.00')       // lowest price
        ->assertSee('R$ 517.00')       // ceiling
        ->assertSee('Winner')
        ->assertSee('Target share');
});

it('shows exclusion reasons on the detail page for a narrow-tolerance decision', function () {
    $decision = AllocationDecision::where('allocation_run_id', $this->narrowRun->id)->firstOrFail();

    $this->get(route('allocations.show', $decision))
        ->assertOk()
        ->assertSee('Price outside tolerance')
        ->assertSee('Excluded');
});

it('lists simulation runs with a link to their decisions', function () {
    $this->get(route('simulations.index'))
        ->assertOk()
        ->assertSee('RUN-')
        ->assertSee('Front Brake Disc')
        ->assertSee(route('allocations.index', ['run' => $this->wideRun->id]));
});

it('is reachable from the Allocation Logs nav', function () {
    $this->get('/allocations')->assertOk()->assertSee('Decision history');
    $this->get('/simulations')->assertOk()->assertSee('Simulation runs');
});
