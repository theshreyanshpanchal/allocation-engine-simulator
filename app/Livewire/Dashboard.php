<?php

namespace App\Livewire;

use App\Allocation\AllocationEngine;
use App\Allocation\AllocationEvaluation;
use App\Allocation\AllocationExplanation;
use App\Allocation\AllocationExplanationService;
use App\Allocation\FeaturedStoreResolver;
use App\Allocation\SellerEvaluation;
use App\Allocation\SimulationOutcome;
use App\Allocation\SimulationService;
use App\Models\AllocationRun;
use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    /**
     * The buyer's postcode. Feeds the price guard's sibling check — whether a
     * store's service area covers this buyer at all (FR-GEO-001/002) — before
     * price or score are ever looked at. The default is covered by every
     * seeded store, so the demo opens with all three eligible.
     */
    public string $buyerPostcode = '01310-100';

    public ?int $productId = null;

    public float $tolerance = 10.0;

    /**
     * Nullable so Livewire can round-trip an empty input. Clearing the field
     * posts "" which cannot be assigned to a non-nullable int; Livewire
     * responds by unsetting the property, and the next read of it blows up
     * with a PropertyNotFoundException. Null is the "field is empty" state;
     * {@see simulate()} clamps it back into range before a run.
     */
    public ?int $purchaseCount = 1000;

    public ?int $lastRunId = null;

    /** Last non-zero tolerance, for the "return to previous value" action. */
    public float $previousTolerance = 10.0;

    public bool $zeroToleranceAcknowledged = false;

    public const TOLERANCE_MIN = 0.0;

    public const TOLERANCE_MAX = 20.0;

    public const TOLERANCE_STEP = 0.5;

    public function mount(): void
    {
        $this->productId ??= Product::query()
            ->where('sku', 'BRAKE-DISC-001')
            ->orderBy('id')
            ->value('id')
            ?? Product::query()->orderBy('name')->value('id');
    }

    public function updatingTolerance(mixed $value): void
    {
        if ((float) $this->tolerance > self::TOLERANCE_MIN) {
            $this->previousTolerance = (float) $this->tolerance;
        }
    }

    public function updatedTolerance(): void
    {
        $this->tolerance = max(
            self::TOLERANCE_MIN,
            min(self::TOLERANCE_MAX, round($this->tolerance / self::TOLERANCE_STEP) * self::TOLERANCE_STEP),
        );

        if ($this->tolerance > self::TOLERANCE_MIN) {
            $this->zeroToleranceAcknowledged = false;
        }

        $this->clearOutcome();
    }

    public function keepZeroTolerance(): void
    {
        $this->zeroToleranceAcknowledged = true;
    }

    public function restorePreviousTolerance(): void
    {
        $this->tolerance = $this->previousTolerance > self::TOLERANCE_MIN ? $this->previousTolerance : 10.0;
        $this->zeroToleranceAcknowledged = false;
        $this->clearOutcome();
    }

    public function updatedProductId(): void
    {
        $this->clearOutcome();
    }

    /**
     * Changing the buyer's postcode can change who's serviceable, which can
     * change the whole survivor set — same treatment as a product change.
     */
    public function updatedBuyerPostcode(): void
    {
        unset($this->evaluation, $this->featuredStore, $this->explanation);
        $this->clearOutcome();
    }

    /**
     * Leave an emptied field empty while the user retypes — clamping null up
     * to 1 on every keystroke would fight the person editing the box.
     */
    public function updatedPurchaseCount(): void
    {
        if ($this->purchaseCount === null) {
            return;
        }

        $this->purchaseCount = max(1, min(100000, $this->purchaseCount));
    }

    private function clearOutcome(): void
    {
        $this->lastRunId = null;
        unset($this->outcome);
        $this->dispatch('simulation-cleared');
    }

    /**
     * Run N simulated purchases through the engine + stateful strategy and
     * persist the full audit trail. Results render via the {@see outcome()}
     * computed so they survive Livewire round-trips.
     */
    public function simulate(): void
    {
        $product = $this->product();

        if (! $product || ! $this->evaluation()?->hasParticipatingSellers()) {
            return;
        }

        $this->purchaseCount = max(1, min(100000, (int) $this->purchaseCount));

        $run = app(SimulationService::class)->run(
            $product,
            $this->tolerance,
            $this->purchaseCount,
            $this->buyerPostcode,
        );

        $this->lastRunId = $run->id;
        unset($this->outcome, $this->featuredStore, $this->explanation);

        $this->dispatch('simulation-updated', payload: $this->chartData());
    }

    #[Computed]
    public function products()
    {
        return Product::orderBy('name')->get();
    }

    #[Computed]
    public function product(): ?Product
    {
        return $this->productId ? Product::with('offers.store')->find($this->productId) : null;
    }

    #[Computed]
    public function evaluation(): ?AllocationEvaluation
    {
        $product = $this->product();

        return $product
            ? app(AllocationEngine::class)->evaluate($product, $this->tolerance, $this->buyerPostcode)
            : null;
    }

    /**
     * The session-stable featured store: picked once per
     * product + tolerance + surviving-seller set via the deficit strategy,
     * then held steady across reloads and "Re-evaluate" (spec section 32).
     */
    #[Computed]
    public function featuredStore(): ?SellerEvaluation
    {
        $evaluation = $this->evaluation();

        return $evaluation
            ? app(FeaturedStoreResolver::class)->resolve($evaluation)
            : null;
    }

    /**
     * Re-run the evaluation without disturbing the featured store — used to
     * demonstrate session stability.
     */
    public function reevaluate(): void
    {
        unset($this->evaluation, $this->featuredStore, $this->product, $this->explanation);
    }

    #[Computed]
    public function outcome(): ?SimulationOutcome
    {
        if ($this->lastRunId === null) {
            return null;
        }

        $run = AllocationRun::find($this->lastRunId);

        return $run ? app(SimulationService::class)->outcomeFor($run) : null;
    }

    /**
     * Datasets for the target-vs-realised bar chart, the convergence line
     * chart and the price-guard impact panel. Null when no run has been made.
     *
     * @return array<string, mixed>|null
     */
    public function chartData(): ?array
    {
        $outcome = $this->outcome();

        if ($outcome === null || $this->lastRunId === null) {
            return null;
        }

        $run = AllocationRun::find($this->lastRunId);
        $convergence = $run ? app(SimulationService::class)->convergenceSeries($run) : null;

        $evaluation = $this->evaluation();
        $priceGuardImpact = [];
        foreach ($evaluation?->excludedByPriceGuard() ?? [] as $seller) {
            $priceGuardImpact[] = [
                'storeName' => $seller->storeName,
                'excludedPurchases' => $outcome->purchaseCount,
            ];
        }

        return [
            'runId' => $outcome->runId,
            'purchaseCount' => $outcome->purchaseCount,
            'bars' => [
                'labels' => array_column($outcome->rows, 'storeName'),
                'target' => array_map(fn ($r) => round($r['targetShare'] * 100, 2), $outcome->rows),
                'realised' => array_map(fn ($r) => round($r['realisedShare'] * 100, 2), $outcome->rows),
            ],
            'convergence' => $convergence,
            'priceGuardImpact' => $priceGuardImpact,
        ];
    }

    #[Computed]
    public function explanation(): ?AllocationExplanation
    {
        $evaluation = $this->evaluation();

        return $evaluation
            ? app(AllocationExplanationService::class)->forEvaluation($evaluation, $this->featuredStore())
            : null;
    }

    public function isZeroTolerance(): bool
    {
        return $this->tolerance <= self::TOLERANCE_MIN;
    }

    public function render()
    {
        return view('livewire.dashboard')->title('Dashboard — Allocation Engine Simulator');
    }
}
