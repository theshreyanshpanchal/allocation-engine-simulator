<?php

use App\Livewire\Dashboard;
use App\Models\Product;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(DemoDataSeeder::class));

it('defaults to the Front Brake Disc at 10% tolerance with all three stores eligible', function () {
    $disc = Product::where('sku', 'BRAKE-DISC-001')->firstOrFail();

    Livewire::test(Dashboard::class)
        ->assertSet('productId', $disc->id)
        ->assertSet('tolerance', 10.0)
        ->assertSee('41.7%')
        ->assertSee('33.3%')
        ->assertSee('25.0%')
        ->assertSee('3 / 3')            // eligible sellers card
        ->assertSee('R$ 470.00')        // lowest price
        ->assertSee('R$ 517.00');       // ceiling at 10%
});

it('names Store A as the featured store in the default scenario', function () {
    Livewire::test(Dashboard::class)
        ->assertSee('Featured store')
        ->assertSeeInOrder(['Current featured store', 'Store A']);
});

it('recomputes live when the tolerance drops to 2%', function () {
    Livewire::test(Dashboard::class)
        ->set('tolerance', 2)
        ->assertSee('R$ 479.40')             // new ceiling
        ->assertSee('Price outside tolerance') // A and B excluded
        ->assertSee('1 / 3');                 // only C participates
});

it('explains the price tolerance guard in both registers, tied to the live numbers', function () {
    Livewire::test(Dashboard::class)
        ->assertSee('What this does')
        ->assertSee('Only stores priced at or below')
        ->assertSee('The formula')
        ->assertSee('ceiling = lowest price × (1 + tolerance ÷ 100)')
        ->assertSee('(1 + 10% ÷ 100)', false)
        ->set('tolerance', 2)
        // The explanation updates with the slider — not a static blurb.
        ->assertSee('(1 + 2% ÷ 100)', false);
});

it('never leaks a class, method, or field name into the technical explanation', function () {
    $html = Livewire::test(Dashboard::class)->html();

    expect($html)->not->toContain('Service::')
        ->and($html)->not->toContain('::apply')
        ->and($html)->not->toContain('EligibilityService')
        ->and($html)->not->toContain('PriceGuardService');
});

it('offers a "how does this work" diagram plotting every store on the real price line', function () {
    $component = Livewire::test(Dashboard::class)
        ->assertSee('How does this work?')
        ->assertSeeHtml('<dialog')
        // All three stores plotted, all green (inside the band) at 10%.
        ->assertSeeHtml('bg-emerald-500')
        ->assertSee('In the zone — still in the running')
        ->assertSee('Priced out — excluded here');

    // At a narrow tolerance, A and B are genuinely priced out of the band —
    // the diagram should show that with a red dot, not a static illustration.
    $component->set('tolerance', 2)->assertSeeHtml('bg-rose-500');
});

it('shows the zero-tolerance administrator warning', function () {
    Livewire::test(Dashboard::class)
        ->assertDontSee('Price guard warning')
        ->set('tolerance', 0)
        ->assertSee('Price guard warning')
        ->assertSee('cheapest always wins');
});

it('clamps the tolerance slider to the 0–20 range', function () {
    Livewire::test(Dashboard::class)
        ->set('tolerance', 25)->assertSet('tolerance', 20.0)
        ->set('tolerance', -5)->assertSet('tolerance', 0.0)
        ->set('tolerance', 7.3)->assertSet('tolerance', 7.5); // snapped to the 0.5 step
});

it('switches the evaluation when a different product is selected', function () {
    $oil = Product::where('sku', 'OIL-FILTER-001')->firstOrFail();

    Livewire::test(Dashboard::class)
        ->set('productId', $oil->id)
        ->assertSee('Engine Oil Filter')
        ->assertSee('R$ 70.00');   // Store C oil-filter price / lowest
});

it('does not depend on the Stores or Products screens', function () {
    // Rendering the dashboard must not require those routes/controllers.
    Livewire::test(Dashboard::class)->assertOk();
});

it('survives the purchase-count field being cleared and still simulates', function () {
    Livewire::test(Dashboard::class)
        // Clearing a number input posts "", which Livewire turns into null for
        // an int-typed property. Against a non-nullable int that unset the
        // property and the updated hook then blew up reading it back.
        ->set('purchaseCount', '')
        ->assertSet('purchaseCount', null)
        ->assertHasNoErrors()
        // An empty box falls back to the default rather than blocking a run.
        ->call('simulate')
        ->assertSet('purchaseCount', 1000)
        ->assertSet('lastRunId', fn ($id) => $id !== null);
});

it('clamps the purchase count to the allowed range as it is typed', function () {
    Livewire::test(Dashboard::class)
        ->set('purchaseCount', 0)
        ->assertSet('purchaseCount', 1)
        ->set('purchaseCount', 250000)
        ->assertSet('purchaseCount', 100000);
});
