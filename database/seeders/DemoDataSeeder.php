<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\Store;
use Illuminate\Database\Seeder;

/**
 * Canonical Project Atlas demo dataset.
 *
 * These values are fixed simulator seed data. The FRD prescribes the
 * three-store 5/4/3 example (-> 41.7 / 33.3 / 25); the five products and
 * their per-store prices are representative demo values, not client figures.
 */
class DemoDataSeeder extends Seeder
{
    /**
     * Store code => [name, operational score, distance in km, service postcode prefixes].
     *
     * Prefixes are illustrative CEP-style 2-digit zones, not real coverage data. Every
     * store includes "01" so the default buyer postcode (01310-100) keeps all three
     * eligible out of the box; the non-overlapping prefixes exist so changing the buyer
     * postcode on the dashboard visibly changes who's in the running (FR-GEO-001/002).
     */
    private const STORES = [
        'STORE-A' => ['Store A', 5.0, 5.0, '01,02,03'],
        'STORE-B' => ['Store B', 4.0, 8.0, '01,04,05'],
        'STORE-C' => ['Store C', 3.0, 12.0, '01,08,09'],
    ];

    /** SKU => [name, [STORE-A price, STORE-B price, STORE-C price]]. */
    private const PRODUCTS = [
        'BRAKE-DISC-001' => ['Front Brake Disc', [500.00, 480.00, 470.00]],
        'BRAKE-PAD-001' => ['Front Brake Pad Set', [320.00, 310.00, 300.00]],
        'OIL-FILTER-001' => ['Engine Oil Filter', [75.00, 72.00, 70.00]],
        'AIR-FILTER-001' => ['Engine Air Filter', [110.00, 105.00, 100.00]],
        'CABIN-FILTER-001' => ['Cabin Air Filter', [95.00, 92.00, 90.00]],
    ];

    public function run(): void
    {
        $stores = [];

        foreach (self::STORES as $code => [$name, $score, $distance, $postcodePrefixes]) {
            $stores[$code] = Store::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'operational_score' => $score,
                    'distance_km' => $distance,
                    'status' => 'active',
                    'serviceable' => true,
                    'service_postcode_prefixes' => $postcodePrefixes,
                ],
            );
        }

        $storeOrder = ['STORE-A', 'STORE-B', 'STORE-C'];

        foreach (self::PRODUCTS as $sku => [$name, $prices]) {
            $product = Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'name' => $name,
                    'brand' => 'Demo Brand',
                    'vehicle_make' => 'Toyota',
                    'vehicle_model' => 'Corolla',
                    'vehicle_year' => 2020,
                    'status' => 'active',
                ],
            );

            foreach ($storeOrder as $i => $code) {
                ProductOffer::updateOrCreate(
                    ['product_id' => $product->id, 'store_id' => $stores[$code]->id],
                    [
                        'price' => $prices[$i],
                        'stock_quantity' => 100,
                        'status' => 'active',
                    ],
                );
            }
        }
    }
}
