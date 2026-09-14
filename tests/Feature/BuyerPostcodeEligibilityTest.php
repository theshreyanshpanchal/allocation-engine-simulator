<?php

use App\Allocation\AllocationEngine;
use App\Allocation\ExclusionReason;
use App\Livewire\Dashboard;
use App\Models\Store;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->engine = new AllocationEngine();
});

it('treats a null buyer postcode as unrestricted, unchanged from before this feature existed', function () {
    $evaluation = $this->engine->evaluate(makeBrakeDiscScenario(), 10);

    expect(collect($evaluation->participating())->pluck('storeCode')->all())->toBe(['A', 'B', 'C']);
});

it('treats a store with no service area configured as unrestricted', function () {
    $evaluation = $this->engine->evaluate(makeBrakeDiscScenario(), 10, '99999-999');

    expect(collect($evaluation->participating())->pluck('storeCode')->all())->toBe(['A', 'B', 'C']);
});

it('excludes stores whose service area does not cover the buyer postcode', function () {
    $product = makeScenario([
        'A' => ['score' => 5, 'price' => 500, 'postcodePrefixes' => '01,02'],
        'B' => ['score' => 4, 'price' => 480, 'postcodePrefixes' => '01,04'],
        'C' => ['score' => 3, 'price' => 470, 'postcodePrefixes' => '01,08'],
    ]);

    // "08500-000" only matches Store C's prefix.
    $evaluation = $this->engine->evaluate($product, 10, '08500-000');

    expect(collect($evaluation->participating())->pluck('storeCode')->all())->toBe(['C']);

    $a = collect($evaluation->sellers)->firstWhere('storeCode', 'A');
    $b = collect($evaluation->sellers)->firstWhere('storeCode', 'B');
    expect($a->exclusionReason)->toBe(ExclusionReason::NOT_SERVICEABLE)
        ->and($b->exclusionReason)->toBe(ExclusionReason::NOT_SERVICEABLE);
});

it('is winnerless with the right reason when no store covers the postcode', function () {
    $product = makeScenario([
        'A' => ['score' => 5, 'price' => 500, 'postcodePrefixes' => '01'],
        'B' => ['score' => 4, 'price' => 480, 'postcodePrefixes' => '01'],
        'C' => ['score' => 3, 'price' => 470, 'postcodePrefixes' => '01'],
    ]);

    $evaluation = $this->engine->evaluate($product, 10, '99999-999');

    expect($evaluation->hasParticipatingSellers())->toBeFalse()
        ->and($evaluation->winnerlessReason())->toBe('No store can serve this buyer.');
});

it('checks the postcode digits regardless of formatting', function () {
    $store = Store::factory()->create(['service_postcode_prefixes' => '01,02']);

    expect($store->coversPostcode('01310-100'))->toBeTrue()
        ->and($store->coversPostcode('01310100'))->toBeTrue()
        ->and($store->coversPostcode('99999-999'))->toBeFalse()
        ->and($store->coversPostcode(null))->toBeTrue();
});

it('demo dataset keeps every seeded store serviceable at the default dashboard postcode', function () {
    $this->seed(DemoDataSeeder::class);

    Livewire::test(Dashboard::class)
        ->assertSet('buyerPostcode', '01310-100')
        ->assertSee('41.7%')
        ->assertSee('33.3%')
        ->assertSee('25.0%');
});

it('changing the buyer postcode on the dashboard narrows the survivor set live', function () {
    $this->seed(DemoDataSeeder::class);

    Livewire::test(Dashboard::class)
        ->set('buyerPostcode', '08500-000')
        ->assertSee('Only one store is inside the price guard')
        ->assertDontSee('No allocation is possible right now');
});

it('shows the friendly no-coverage message when nothing serves the postcode', function () {
    $this->seed(DemoDataSeeder::class);

    Livewire::test(Dashboard::class)
        ->set('buyerPostcode', '99999-999')
        ->assertSee('No allocation is possible right now')
        ->assertSee('No store can serve this buyer');
});
