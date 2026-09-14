<?php

namespace App\Allocation;

use App\Models\AllocationDecision;
use App\Models\AllocationRun;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Runs N virtual purchases through the engine + the deficit strategy and
 * writes a full audit trail: one {@see AllocationRun}, one
 * {@see AllocationDecision} per purchase, and one
 * {@see \App\Models\AllocationDecisionSeller} per store involved in each
 * decision (spec sections 63–64, 117).
 *
 * Each run's strategy state starts empty (spec section 31) so the realised
 * distribution converges toward target within the run. The persisted
 * {@see \App\Models\AllocationState} still accumulates run totals per context.
 */
class SimulationService
{
    /** Bulk-insert chunk size — keeps well under MySQL's placeholder limit. */
    private const CHUNK = 500;

    public function __construct(
        private readonly AllocationEngine $engine = new AllocationEngine(),
        private readonly DeficitAllocationStrategy $strategy = new DeficitAllocationStrategy(),
        private readonly AllocationStateStore $stateStore = new AllocationStateStore(),
    ) {
    }

    public function run(Product $product, float $tolerancePercent, int $purchaseCount, ?string $buyerPostcode = null): AllocationRun
    {
        $purchaseCount = max(1, min(100000, $purchaseCount));
        $evaluation = $this->engine->evaluate($product, $tolerancePercent, $buyerPostcode);

        return DB::transaction(function () use ($product, $evaluation, $tolerancePercent, $purchaseCount, $buyerPostcode) {
            $run = AllocationRun::create([
                'product_id' => $product->id,
                'buyer_postcode' => $buyerPostcode,
                'tolerance_percent' => $evaluation->tolerancePercent,
                'purchase_count' => $purchaseCount,
                'started_at' => now(),
            ]);

            $participating = $evaluation->participating();
            $contextKey = $evaluation->eligibleContextKey();
            $now = now();

            if ($participating === []) {
                // Nothing to allocate — record the run and a single winnerless decision.
                $this->persistDecisions($run, $evaluation, [null], $buyerPostcode, $now);
                $run->forceFill(['completed_at' => now()])->save();

                return $run->refresh();
            }

            // Fold N purchases with run-local state.
            $state = new RealisedState();
            $winners = [];
            $winCounts = [];

            for ($i = 0; $i < $purchaseCount; $i++) {
                $winner = $this->strategy->select($evaluation, $state);
                $winners[] = $winner->storeId;
                $winCounts[$winner->storeId] = ($winCounts[$winner->storeId] ?? 0) + 1;
                $state = $state->withAllocation($winner->storeId);
            }

            $this->persistDecisions($run, $evaluation, $winners, $buyerPostcode, $now);

            $targetShares = [];
            foreach ($participating as $seller) {
                $targetShares[$seller->storeId] = $seller->targetShare;
            }
            $this->stateStore->addRunTotals($product->id, $contextKey, $winCounts, $purchaseCount, $targetShares);

            $run->forceFill(['completed_at' => now()])->save();

            return $run->refresh();
        });
    }

    /**
     * Rebuild the aggregate outcome for a finished run from its persisted rows.
     */
    public function outcomeFor(AllocationRun $run): SimulationOutcome
    {
        $run->loadMissing('product');
        $evaluation = $this->engine->evaluate($run->product, (float) $run->tolerance_percent, $run->buyer_postcode);

        $allocationsByStore = AllocationDecision::query()
            ->where('allocation_run_id', $run->id)
            ->whereNotNull('winner_store_id')
            ->selectRaw('winner_store_id, count(*) as c')
            ->groupBy('winner_store_id')
            ->pluck('c', 'winner_store_id');

        $total = max(1, (int) $allocationsByStore->sum());

        $rows = [];
        foreach ($evaluation->participating() as $seller) {
            $count = (int) ($allocationsByStore[$seller->storeId] ?? 0);
            $realised = $count / $total;

            $rows[] = [
                'storeId' => $seller->storeId,
                'storeCode' => $seller->storeCode,
                'storeName' => $seller->storeName,
                'score' => $seller->score,
                'targetShare' => $seller->targetShare,
                'realisedShare' => $realised,
                'differencePoints' => round(($realised - $seller->targetShare) * 100, 2),
                'allocations' => $count,
            ];
        }

        $priceGuardExclusions = [];
        foreach ($evaluation->excludedByPriceGuard() as $seller) {
            $priceGuardExclusions[$seller->storeId] = (int) $run->purchase_count;
        }

        return new SimulationOutcome(
            runId: $run->id,
            purchaseCount: (int) $run->purchase_count,
            tolerancePercent: (float) $run->tolerance_percent,
            lowestPrice: $evaluation->lowestPrice,
            priceCeiling: $evaluation->priceCeiling,
            rows: $rows,
            priceGuardExclusions: $priceGuardExclusions,
        );
    }

    /**
     * Realised share per participating store sampled across the run, for the
     * convergence chart. Returns up to $maxPoints evenly-spaced samples plus
     * the final point.
     *
     * @return array{labels: list<int>, targets: array<string,float>, series: array<string, list<float>>, names: array<string,string>}
     */
    public function convergenceSeries(AllocationRun $run, int $maxPoints = 60): array
    {
        $run->loadMissing('product');
        $evaluation = $this->engine->evaluate($run->product, (float) $run->tolerance_percent, $run->buyer_postcode);
        $participating = $evaluation->participating();

        $codeById = [];
        $names = [];
        $targets = [];
        $series = [];
        foreach ($participating as $seller) {
            $codeById[$seller->storeId] = $seller->storeCode;
            $names[$seller->storeCode] = $seller->storeName;
            $targets[$seller->storeCode] = round($seller->targetShare * 100, 2);
            $series[$seller->storeCode] = [];
        }

        $winners = AllocationDecision::query()
            ->where('allocation_run_id', $run->id)
            ->whereNotNull('winner_store_id')
            ->orderBy('purchase_index')
            ->pluck('winner_store_id')
            ->all();

        $n = count($winners);
        if ($n === 0) {
            return ['labels' => [], 'targets' => $targets, 'series' => $series, 'names' => $names];
        }

        $step = max(1, (int) ceil($n / $maxPoints));
        $counts = array_fill_keys(array_keys($series), 0);
        $labels = [];

        foreach ($winners as $i => $storeId) {
            $code = $codeById[$storeId] ?? null;
            if ($code !== null) {
                $counts[$code]++;
            }

            $position = $i + 1;
            if ($position % $step === 0 || $position === $n) {
                $labels[] = $position;
                foreach ($series as $code => $_) {
                    $series[$code][] = round($counts[$code] / $position * 100, 2);
                }
            }
        }

        return ['labels' => $labels, 'targets' => $targets, 'series' => $series, 'names' => $names];
    }

    /**
     * @param  list<int|null>  $winners  winner store id per purchase (null = winnerless)
     */
    private function persistDecisions(
        AllocationRun $run,
        AllocationEvaluation $evaluation,
        array $winners,
        ?string $buyerPostcode,
        \DateTimeInterface $now,
    ): void {
        $contextKey = $evaluation->eligibleContextKey();
        $reason = $this->decisionReason($evaluation);

        $decisionRows = [];
        foreach ($winners as $index => $winnerId) {
            $decisionRows[] = [
                'allocation_run_id' => $run->id,
                'product_id' => $run->product_id,
                'winner_store_id' => $winnerId,
                'purchase_index' => $index + 1,
                'buyer_postcode' => $buyerPostcode,
                'tolerance_percent' => $evaluation->tolerancePercent,
                'lowest_price' => $evaluation->lowestPrice,
                'price_ceiling' => $evaluation->priceCeiling,
                'eligible_context' => $contextKey,
                'decision_reason' => $reason,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($decisionRows, self::CHUNK) as $chunk) {
            DB::table('allocation_decisions')->insert($chunk);
        }

        $decisionIds = AllocationDecision::query()
            ->where('allocation_run_id', $run->id)
            ->orderBy('purchase_index')
            ->pluck('id', 'purchase_index');

        $sellerRows = [];
        foreach ($winners as $index => $winnerId) {
            $decisionId = $decisionIds[$index + 1];

            foreach ($evaluation->sellers as $seller) {
                $sellerRows[] = [
                    'allocation_decision_id' => $decisionId,
                    'store_id' => $seller->storeId,
                    'price' => $seller->price,
                    'score' => $seller->score,
                    'distance_km' => $seller->distanceKm,
                    'stock_available' => $seller->stockAvailable,
                    'serviceable' => $seller->serviceable,
                    'price_guard_eligible' => $seller->priceGuardEligible,
                    'exclusion_reason' => $seller->participating ? null : $seller->exclusionReason,
                    'target_share' => $seller->targetShare,
                    'is_winner' => $winnerId !== null && $seller->storeId === $winnerId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($sellerRows, self::CHUNK) as $chunk) {
            DB::table('allocation_decision_sellers')->insert($chunk);
        }
    }

    private function decisionReason(AllocationEvaluation $evaluation): string
    {
        if (! $evaluation->hasParticipatingSellers()) {
            return $evaluation->winnerlessReason() ?? 'No eligible stores.';
        }

        $codes = array_map(fn (SellerEvaluation $s) => $s->storeCode, $evaluation->participating());

        return count($codes) === 1
            ? "Only {$codes[0]} was eligible inside the price guard."
            : 'Score-weighted allocation across '.implode(', ', $codes).' inside the price guard.';
    }
}
