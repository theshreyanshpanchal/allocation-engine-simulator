<?php

use App\Models\Product;
use App\Models\Store;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(DemoDataSeeder::class));

it('lists the three stores with score, distance and product count', function () {
    $response = $this->get('/stores')->assertOk();

    foreach (Store::all() as $store) {
        $response->assertSee($store->name)
            ->assertSee($store->score_label.' / 5', false)
            ->assertSee($store->distance_label);
    }

    $response->assertSee('>5<', false) // Store A offer count
        ->assertSee('What do these terms mean?')
        ->assertSee('Operational score')
        ->assertSee('Serviceability');
});

it('shows a store detail with its offers and field explanations', function () {
    $store = Store::where('code', 'STORE-A')->firstOrFail();

    $this->get(route('stores.show', $store))
        ->assertOk()
        ->assertSee('Store A')
        ->assertSee('STORE-A')
        ->assertSee('Eligible for buyer')
        ->assertSee('Front Brake Disc')
        ->assertSee('R$ 500.00')
        // Contextual, per-store one-liners tied to this store's actual values.
        ->assertSee('Open for business')
        ->assertSee("Can deliver to this buyer's area.")
        // The shared plain/technical reference table.
        ->assertSee('What do these terms mean?')
        ->assertSee('ceiling = lowest price', false);
});

it('never leaks a class, method, or field name into the store glossary', function () {
    $html = $this->get('/stores')->getContent();

    expect($html)->not->toContain('EligibilityService')
        ->and($html)->not->toContain('PriceGuardService')
        ->and($html)->not->toContain('::')
        ->and($html)->not->toContain('_score')
        ->and($html)->not->toContain('_km')
        ->and($html)->not->toContain('_quantity')
        ->and($html)->not->toContain(' Model.')
        ->and($html)->not->toContain('Store model')
        ->and($html)->not->toContain('ProductOffer');
});

it('explains an inactive or non-serviceable store with the matching plain-language note', function () {
    $store = Store::where('code', 'STORE-A')->firstOrFail();
    $store->update(['status' => 'inactive', 'serviceable' => false]);

    $this->get(route('stores.show', $store))
        ->assertOk()
        ->assertSee("Closed — excluded from every order until it's reactivated.")
        ->assertSee("Can't deliver here — excluded regardless of price or score.");
});

it('lists the five products with a price column per store', function () {
    $response = $this->get('/products')->assertOk();

    foreach (Product::all() as $product) {
        $response->assertSee($product->name);
    }

    $response->assertSee('Store A')
        ->assertSee('Store B')
        ->assertSee('Store C')
        ->assertSee('R$ 470.00'); // Brake Disc @ Store C
});

it('shows a product detail with an offer block per store', function () {
    $product = Product::where('sku', 'BRAKE-DISC-001')->firstOrFail();

    $this->get(route('products.show', $product))
        ->assertOk()
        ->assertSee('Front Brake Disc')
        ->assertSee('Toyota Corolla 2020')
        ->assertSee('R$ 500.00')
        ->assertSee('R$ 480.00')
        ->assertSee('R$ 470.00')
        ->assertSee('Available');
});

it('links products and stores to each other', function () {
    $product = Product::first();
    $store = Store::first();

    $this->get(route('products.show', $product))->assertSee(route('stores.show', $store));
    $this->get(route('stores.show', $store))->assertSee(route('products.show', $product));
});
