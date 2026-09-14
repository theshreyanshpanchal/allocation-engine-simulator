<?php

use App\Allocation\SimulationService;
use App\Livewire\Dashboard;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(SimulationService::class);
});

it('builds a convergence series that ends at the purchase count and near the targets', function () {
    $run = $this->service->run(makeBrakeDiscScenario(), 10, 600);

    $series = $this->service->convergenceSeries($run, 30);

    expect($series['labels'])->not->toBeEmpty()
        ->and(end($series['labels']))->toBe(600)
        ->and(array_keys($series['series']))->toBe(['A', 'B', 'C'])
        ->and($series['targets']['A'])->toEqualWithDelta(41.67, 0.01);

    foreach ($series['series'] as $code => $points) {
        expect(count($points))->toBe(count($series['labels']));
        expect(abs(end($points) - $series['targets'][$code]))->toBeLessThan(2.0);
    }
});

it('returns null chart data before any run and full datasets afterwards', function () {
    $this->seed(DemoDataSeeder::class);
    $component = Livewire::test(Dashboard::class);

    expect($component->instance()->chartData())->toBeNull();

    $component->set('purchaseCount', 300)->call('simulate');

    $data = $component->instance()->chartData();

    expect($data)->toBeArray()
        ->and($data['bars']['labels'])->toBe(['Store A', 'Store B', 'Store C'])
        ->and($data['bars']['target'])->toHaveCount(3)
        ->and($data['bars']['realised'])->toHaveCount(3)
        ->and(array_sum($data['bars']['target']))->toEqualWithDelta(100.0, 0.5)
        ->and($data['convergence']['labels'])->not->toBeEmpty()
        ->and($data['priceGuardImpact'])->toBe([]);
});

it('reports the price-guard impact for stores cut at a narrow tolerance', function () {
    $this->seed(DemoDataSeeder::class);

    $component = Livewire::test(Dashboard::class)
        ->set('tolerance', 2)
        ->set('purchaseCount', 100)
        ->call('simulate');

    $impact = $component->instance()->chartData()['priceGuardImpact'];

    expect($impact)->toHaveCount(2)
        ->and(collect($impact)->pluck('storeName')->sort()->values()->all())->toBe(['Store A', 'Store B'])
        ->and($impact[0]['excludedPurchases'])->toBe(100);
});

it('dispatches chart events on simulate and on tolerance change', function () {
    $this->seed(DemoDataSeeder::class);

    Livewire::test(Dashboard::class)
        ->set('purchaseCount', 50)
        ->call('simulate')
        ->assertDispatched('simulation-updated')
        ->set('tolerance', 6)
        ->assertDispatched('simulation-cleared');
});

it('renders the analytics section after a run', function () {
    $this->seed(DemoDataSeeder::class);

    Livewire::test(Dashboard::class)
        ->set('purchaseCount', 50)
        ->call('simulate')
        ->assertSee('Target vs realised')
        ->assertSee('Convergence over time')
        ->assertSee('Allocation impact')
        ->assertSeeHtml('x-ref="bar"')
        ->assertSeeHtml('x-ref="conv"');
});
