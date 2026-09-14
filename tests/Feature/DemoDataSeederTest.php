<?php

use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\Store;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(DemoDataSeeder::class));

it('seeds exactly three stores with the FRD scores and demo distances', function () {
    expect(Store::count())->toBe(3);

    $stores = Store::orderBy('code')->get()->keyBy('code');

    expect($stores['STORE-A']->operational_score)->toBe(5.0)
        ->and($stores['STORE-B']->operational_score)->toBe(4.0)
        ->and($stores['STORE-C']->operational_score)->toBe(3.0)
        ->and($stores['STORE-A']->distance_km)->toBe(5.0)
        ->and($stores['STORE-B']->distance_km)->toBe(8.0)
        ->and($stores['STORE-C']->distance_km)->toBe(12.0);

    Store::each(function (Store $store) {
        expect($store->status)->toBe('active')
            ->and($store->serviceable)->toBeTrue();
    });
});

it('seeds exactly five products, all fitted to the Toyota Corolla 2020', function () {
    expect(Product::count())->toBe(5);

    Product::each(function (Product $product) {
        expect($product->vehicle_label)->toBe('Toyota Corolla 2020');
    });

    expect(Product::pluck('name')->sort()->values()->all())->toBe([
        'Cabin Air Filter',
        'Engine Air Filter',
        'Engine Oil Filter',
        'Front Brake Disc',
        'Front Brake Pad Set',
    ]);
});

it('gives every product an in-stock offer at all three stores (15 offers)', function () {
    expect(ProductOffer::count())->toBe(15);

    Product::with('offers')->each(function (Product $product) {
        expect($product->offers)->toHaveCount(3);
        $product->offers->each(fn (ProductOffer $offer) => expect($offer->stock_quantity)->toBeGreaterThan(0)
            ->and($offer->status)->toBe('active'));
    });
});

it('seeds the Front Brake Disc price matrix from the spec', function () {
    $disc = Product::where('sku', 'BRAKE-DISC-001')->firstOrFail();

    $prices = $disc->offers()
        ->join('stores', 'stores.id', '=', 'product_offers.store_id')
        ->pluck('product_offers.price', 'stores.code');

    expect((float) $prices['STORE-A'])->toBe(500.00)
        ->and((float) $prices['STORE-B'])->toBe(480.00)
        ->and((float) $prices['STORE-C'])->toBe(470.00);
});

it('is idempotent when run twice', function () {
    $this->seed(DemoDataSeeder::class);

    expect(Store::count())->toBe(3)
        ->and(Product::count())->toBe(5)
        ->and(ProductOffer::count())->toBe(15);
});
