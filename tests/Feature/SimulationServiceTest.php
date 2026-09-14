<?php

use App\Allocation\ExclusionReason;
use App\Allocation\SimulationService;
use App\Livewire\Dashboard;
use App\Models\AllocationDecision;
use App\Models\AllocationDecisionSeller;
use App\Models\AllocationState;
use App\Models\Product;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(SimulationService::class);
});

it('records a run with a start and completion time', function () {
    $run = $this->service->run(makeBrakeDiscScenario(), 10, 50);

    expect($run->purchase_count)->toBe(50)
        ->and($run->tolerance_percent)->toBe(10.0)
        ->and($run->started_at)->not->toBeNull()
        ->and($run->completed_at)->not->toBeNull();
});

it('writes one decision per purchase, numbered from 1', function () {
    $run = $this->service->run(makeBrakeDiscScenario(), 10, 40);

    $decisions = AllocationDecision::where('allocation_run_id', $run->id)->orderBy('purchase_index')->get();

    expect($decisions)->toHaveCount(40)
        ->and($decisions->first()->purchase_index)->toBe(1)
        ->and($decisions->last()->purchase_index)->toBe(40)
        ->and($decisions->pluck('winner_store_id')->filter()->count())->toBe(40);
});

it('writes an audit row for every store in every decision (spec Test 7)', function () {
    $product = makeBrakeDiscScenario();
    $run = $this->service->run($product, 10, 10);

    $decision = AllocationDecision::where('allocation_run_id', $run->id)->firstOrFail();
    $sellers = AllocationDecisionSeller::where('allocation_decision_id', $decision->id)->get();

    expect($sellers)->toHaveCount(3)
        ->and($decision->lowest_price)->toBe(470.0)
        ->and($decision->price_ceiling)->toBe(517.0)
        ->and($decision->tolerance_percent)->toBe(10.0)
        ->and($decision->decision_reason)->not->toBeNull();

    $sellers->each(function (AllocationDecisionSeller $s) {
        expect($s->price)->not->toBeNull()
            ->and($s->score)->not->toBeNull()
            ->and($s->target_share)->not->toBeNull()
            ->and($s->price_guard_eligible)->toBeTrue();
    });

    expect($sellers->where('is_winner', true))->toHaveCount(1);
});

it('records excluded stores with the price-guard reason at a narrow tolerance', function () {
    $product = makeBrakeDiscScenario();
    $run = $this->service->run($product, 2, 20);

    $decision = AllocationDecision::where('allocation_run_id', $run->id)->firstOrFail();
    $sellers = AllocationDecisionSeller::where('allocation_decision_id', $decision->id)->get()->keyBy('store_id');

    $excluded = $sellers->where('price_guard_eligible', false);
    expect($excluded)->toHaveCount(2);
    $excluded->each(fn ($s) => expect($s->exclusion_reason)->toBe(ExclusionReason::PRICE_OUTSIDE_TOLERANCE));

    // Every purchase is won by the single surviving store.
    expect(AllocationDecision::where('allocation_run_id', $run->id)
        ->distinct('winner_store_id')->count('winner_store_id'))->toBe(1);
});

it('produces a realised distribution that tracks the target over 1000 purchases', function () {
    $product = makeBrakeDiscScenario();
    $run = $this->service->run($product, 10, 1000);

    $outcome = $this->service->outcomeFor($run);

    expect($outcome->rows)->toHaveCount(3);

    $byCode = collect($outcome->rows)->keyBy('storeCode');
    expect($byCode['A']['targetShare'])->toEqualWithDelta(5 / 12, 1e-9);

    foreach ($outcome->rows as $row) {
        expect(abs($row['realisedShare'] - $row['targetShare']))->toBeLessThan(0.02);
        expect($row['allocations'])->toBeGreaterThan(0);
    }

    expect(array_sum(array_column($outcome->rows, 'allocations')))->toBe(1000);
});

it('accumulates run totals into allocation_state per context', function () {
    $product = makeBrakeDiscScenario();

    $this->service->run($product, 10, 100);
    $this->service->run($product, 10, 100);

    $rows = AllocationState::where('product_id', $product->id)
        ->where('eligible_context', 'A|B|C')
        ->get();

    expect($rows)->toHaveCount(3)
        ->and($rows->sum('total_count'))->toBe(600)      // 3 stores * 200 purchases
        ->and($rows->sum('allocated_count'))->toBe(200); // 2 runs * 100 winners
});

it('completes a 2000-purchase run quickly', function () {
    $start = microtime(true);
    $this->service->run(makeBrakeDiscScenario(), 10, 2000);

    expect(microtime(true) - $start)->toBeLessThan(5.0);
});

it('is driven by the dashboard Simulate button', function () {
    $this->seed(DemoDataSeeder::class);

    $component = Livewire::test(Dashboard::class)
        ->set('purchaseCount', 200)
        ->call('simulate')
        ->assertSet('lastRunId', fn ($id) => $id !== null)
        ->assertSee('Simulation completed')
        ->assertSee('Allocations');

    expect(App\Models\AllocationDecision::where('allocation_run_id', $component->get('lastRunId'))->count())
        ->toBe(200);
});

it('clears a stale outcome when the tolerance changes', function () {
    $this->seed(DemoDataSeeder::class);

    Livewire::test(Dashboard::class)
        ->set('purchaseCount', 50)
        ->call('simulate')
        ->assertSet('lastRunId', fn ($id) => $id !== null)
        ->set('tolerance', 5)
        ->assertSet('lastRunId', null)
        ->assertDontSee('Simulation completed');
});
