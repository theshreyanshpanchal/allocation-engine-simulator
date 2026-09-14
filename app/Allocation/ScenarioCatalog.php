<?php

namespace App\Allocation;

use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;

/**
 * The Scenario Playbook: a fixed set of hand-picked cases, each isolating one
 * mechanism of the allocation engine (eligibility ordering, the price guard's
 * three regimes, geography, score edge cases) so they can be walked through
 * one at a time.
 *
 * Every result here is computed live by the real {@see AllocationEngine} —
 * none of the numbers are hand-typed. The stores and products are built
 * in-memory only (never persisted), so running this never touches the
 * seeded demo data.
 */
class ScenarioCatalog
{
    public function __construct(
        private readonly AllocationEngine $engine = new AllocationEngine(),
        private readonly AllocationExplanationService $explanations = new AllocationExplanationService(),
        private readonly DeficitAllocationStrategy $strategy = new DeficitAllocationStrategy(),
    ) {
    }

    /** @return list<ScenarioResult> */
    public function all(): array
    {
        return array_map(fn (ScenarioDefinition $def) => $this->run($def), $this->definitions());
    }

    private function run(ScenarioDefinition $def): ScenarioResult
    {
        $product = $this->buildProduct($def);
        $evaluation = $this->engine->evaluate($product, $def->tolerancePercent, $def->buyerPostcode);

        // Same pick the dashboard would show on a fresh session (no purchase
        // history yet): deficit-vs-target with an empty RealisedState reduces
        // to "highest target share wins, ties by score then store code". This
        // is what makes "Selected: …" below meaningful for the multi-store
        // split scenarios too, not just the single-survivor ones.
        $winner = $this->strategy->select($evaluation, new RealisedState());
        $explanation = $this->explanations->forEvaluation($evaluation, $winner);

        return new ScenarioResult($def, $evaluation, $explanation);
    }

    private function buildProduct(ScenarioDefinition $def): Product
    {
        $product = new Product([
            'sku' => 'SCENARIO',
            'name' => 'Scenario product',
            'status' => 'active',
        ]);
        $product->id = 0;

        $offers = new Collection();

        foreach ($def->stores as $i => $spec) {
            $store = new Store([
                'code' => $spec['code'],
                'name' => $spec['name'],
                'operational_score' => $spec['score'],
                'distance_km' => $spec['distance'] ?? 10,
                'status' => ($spec['active'] ?? true) ? 'active' : 'inactive',
                'serviceable' => $spec['serviceable'] ?? true,
                'service_postcode_prefixes' => $spec['postcodePrefixes'] ?? null,
            ]);
            $store->id = $i + 1;

            $offer = new ProductOffer([
                'price' => $spec['price'],
                'stock_quantity' => $spec['stock'] ?? 100,
                'status' => 'active',
            ]);
            $offer->id = $i + 1;
            $offer->setRelation('store', $store);

            $offers->push($offer);
        }

        $product->setRelation('offers', $offers);

        return $product;
    }

    /** @return list<ScenarioDefinition> */
    private function definitions(): array
    {
        $frdThree = [
            ['code' => 'A', 'name' => 'Store A', 'score' => 5.0, 'price' => 500.0, 'distance' => 5.0],
            ['code' => 'B', 'name' => 'Store B', 'score' => 4.0, 'price' => 480.0, 'distance' => 8.0],
            ['code' => 'C', 'name' => 'Store C', 'score' => 3.0, 'price' => 470.0, 'distance' => 12.0],
        ];

        return [
            new ScenarioDefinition(
                title: '1. Baseline — the FRD worked example',
                summary: 'Wide enough tolerance that price excludes nobody, so the split is pure score-weighting: score ÷ sum of scores.',
                frIds: ['FR-GEO-003'],
                tolerancePercent: 10,
                stores: $frdThree,
            ),
            new ScenarioDefinition(
                title: '2. Narrow tolerance — price decides',
                summary: 'Shrinking the band to 2% pushes A and B outside it. Only the cheapest store is left, so it takes everything — score never gets consulted.',
                frIds: ['FR-GEO-010'],
                tolerancePercent: 2,
                stores: $frdThree,
            ),
            new ScenarioDefinition(
                title: '3. Mid-band tolerance — partial exclusion, re-normalised',
                summary: 'At 2.5%, only A falls outside the band. B and C split the traffic between just the two of them — their scores re-normalise over a smaller pool.',
                frIds: ['FR-GEO-010', 'FR-GEO-003'],
                tolerancePercent: 2.5,
                stores: $frdThree,
            ),
            new ScenarioDefinition(
                title: '4. Zero tolerance — cheapest always wins',
                summary: 'The extreme end of the same lever: the ceiling collapses onto the lowest price, so only the cheapest store can qualify, regardless of score.',
                frIds: ['FR-GEO-010'],
                tolerancePercent: 0,
                stores: $frdThree,
            ),
            new ScenarioDefinition(
                title: '5. Zero tolerance, tied price — score still decides',
                summary: 'Zero tolerance does not always mean one winner: if every store shares the same lowest price, they all stay inside the (zero-width) band, and score-weighting resumes exactly as in case 1.',
                frIds: ['FR-GEO-010', 'FR-GEO-003'],
                tolerancePercent: 0,
                stores: [
                    ['code' => 'A', 'name' => 'Store A', 'score' => 5.0, 'price' => 470.0, 'distance' => 5.0],
                    ['code' => 'B', 'name' => 'Store B', 'score' => 4.0, 'price' => 470.0, 'distance' => 8.0],
                    ['code' => 'C', 'name' => 'Store C', 'score' => 3.0, 'price' => 470.0, 'distance' => 12.0],
                ],
            ),
            new ScenarioDefinition(
                title: '6. Inactive store — excluded before price is even checked',
                summary: 'Store A is closed for business. It is removed at the very first eligibility check, before its price or score are ever looked at — a different reason than losing the price guard.',
                frIds: ['FR-GEO-001', 'FR-GEO-002'],
                tolerancePercent: 10,
                stores: [
                    ['code' => 'A', 'name' => 'Store A', 'score' => 5.0, 'price' => 500.0, 'distance' => 5.0, 'active' => false],
                    ['code' => 'B', 'name' => 'Store B', 'score' => 4.0, 'price' => 480.0, 'distance' => 8.0],
                    ['code' => 'C', 'name' => 'Store C', 'score' => 3.0, 'price' => 470.0, 'distance' => 12.0],
                ],
            ),
            new ScenarioDefinition(
                title: '7. Out of stock — excluded regardless of price or score',
                summary: 'Store A has the best price and the best score, but no stock. It never enters the running at all — stock is checked ahead of price and score.',
                frIds: ['FR-GEO-001', 'FR-GEO-002'],
                tolerancePercent: 10,
                stores: [
                    ['code' => 'A', 'name' => 'Store A', 'score' => 5.0, 'price' => 450.0, 'distance' => 5.0, 'stock' => 0],
                    ['code' => 'B', 'name' => 'Store B', 'score' => 4.0, 'price' => 480.0, 'distance' => 8.0],
                    ['code' => 'C', 'name' => 'Store C', 'score' => 3.0, 'price' => 470.0, 'distance' => 12.0],
                ],
            ),
            new ScenarioDefinition(
                title: '8. Nobody can fulfil — winnerless',
                summary: 'Every store is out of stock for this product. There is no eligible seller at all, so the engine returns a clear reason instead of guessing.',
                frIds: ['FR-GEO-006'],
                tolerancePercent: 10,
                stores: [
                    ['code' => 'A', 'name' => 'Store A', 'score' => 5.0, 'price' => 500.0, 'distance' => 5.0, 'stock' => 0],
                    ['code' => 'B', 'name' => 'Store B', 'score' => 4.0, 'price' => 480.0, 'distance' => 8.0, 'stock' => 0],
                    ['code' => 'C', 'name' => 'Store C', 'score' => 3.0, 'price' => 470.0, 'distance' => 12.0, 'stock' => 0],
                ],
            ),
            new ScenarioDefinition(
                title: "9. Buyer outside two stores' service areas — geography decides first",
                summary: "This buyer's postcode only falls inside Store C's service area. A and B are excluded before price or score are considered — the same ordering as the inactive/out-of-stock cases, just a different check.",
                frIds: ['FR-GEO-001', 'FR-GEO-002'],
                tolerancePercent: 10,
                buyerPostcode: '08500-000',
                stores: [
                    ['code' => 'A', 'name' => 'Store A', 'score' => 5.0, 'price' => 500.0, 'distance' => 5.0, 'postcodePrefixes' => '01,02'],
                    ['code' => 'B', 'name' => 'Store B', 'score' => 4.0, 'price' => 480.0, 'distance' => 8.0, 'postcodePrefixes' => '01,04'],
                    ['code' => 'C', 'name' => 'Store C', 'score' => 3.0, 'price' => 470.0, 'distance' => 12.0, 'postcodePrefixes' => '01,08'],
                ],
            ),
            new ScenarioDefinition(
                title: '10. No store covers the buyer at all — winnerless via geography',
                summary: 'Same three stores and service areas as case 9, but this postcode falls in nobody\'s territory. Geography alone produces a winnerless result — a pricing decision would never even come up.',
                frIds: ['FR-GEO-001', 'FR-GEO-002', 'FR-GEO-006'],
                tolerancePercent: 10,
                buyerPostcode: '99999-999',
                stores: [
                    ['code' => 'A', 'name' => 'Store A', 'score' => 5.0, 'price' => 500.0, 'distance' => 5.0, 'postcodePrefixes' => '01,02'],
                    ['code' => 'B', 'name' => 'Store B', 'score' => 4.0, 'price' => 480.0, 'distance' => 8.0, 'postcodePrefixes' => '01,04'],
                    ['code' => 'C', 'name' => 'Store C', 'score' => 3.0, 'price' => 470.0, 'distance' => 12.0, 'postcodePrefixes' => '01,08'],
                ],
            ),
            new ScenarioDefinition(
                title: '11. Equal scores — a pure even split',
                summary: 'All three stores are equally reliable (same score), and all fall inside the price band. With scores tied, the split is a plain three-way even share — price differences among survivors never tip it.',
                frIds: ['FR-GEO-003'],
                tolerancePercent: 10,
                stores: [
                    ['code' => 'A', 'name' => 'Store A', 'score' => 4.0, 'price' => 500.0, 'distance' => 5.0],
                    ['code' => 'B', 'name' => 'Store B', 'score' => 4.0, 'price' => 480.0, 'distance' => 8.0],
                    ['code' => 'C', 'name' => 'Store C', 'score' => 4.0, 'price' => 470.0, 'distance' => 12.0],
                ],
            ),
        ];
    }
}
