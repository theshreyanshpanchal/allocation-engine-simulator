<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Build a product with three stores keyed A/B/C.
 *
 * @param  array<string, array{score: float|int, price: float|int, distance?: float|int, active?: bool, stock?: int, serviceable?: bool, postcodePrefixes?: ?string}>  $stores
 */
function makeScenario(array $stores): App\Models\Product
{
    $product = App\Models\Product::factory()->create();

    foreach ($stores as $code => $spec) {
        $store = App\Models\Store::factory()->create([
            'code' => $code,
            'name' => "Store {$code}",
            'operational_score' => $spec['score'],
            'distance_km' => $spec['distance'] ?? 10,
            'status' => ($spec['active'] ?? true) ? 'active' : 'inactive',
            'serviceable' => $spec['serviceable'] ?? true,
            'service_postcode_prefixes' => $spec['postcodePrefixes'] ?? null,
        ]);

        App\Models\ProductOffer::factory()->create([
            'product_id' => $product->id,
            'store_id' => $store->id,
            'price' => $spec['price'],
            'stock_quantity' => $spec['stock'] ?? 100,
        ]);
    }

    return $product->load('offers.store');
}

/** The FRD default: scores 5/4/3, prices 500/480/470. */
function makeBrakeDiscScenario(): App\Models\Product
{
    return makeScenario([
        'A' => ['score' => 5, 'price' => 500, 'distance' => 5],
        'B' => ['score' => 4, 'price' => 480, 'distance' => 8],
        'C' => ['score' => 3, 'price' => 470, 'distance' => 12],
    ]);
}

/**
 * @return array<string, float>  store code => target share %
 */
function participatingSharesPercent(App\Allocation\AllocationEvaluation $evaluation): array
{
    $out = [];
    foreach ($evaluation->participating() as $seller) {
        $out[$seller->storeCode] = $seller->targetSharePercent();
    }

    return $out;
}
