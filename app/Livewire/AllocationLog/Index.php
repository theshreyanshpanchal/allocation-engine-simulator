<?php

namespace App\Livewire\AllocationLog;

use App\Models\AllocationDecision;
use App\Models\Product;
use App\Models\Store;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url(as: 'product')]
    public ?int $productId = null;

    #[Url(as: 'winner')]
    public ?int $winnerStoreId = null;

    #[Url(as: 'store')]
    public ?int $involvedStoreId = null;

    #[Url(as: 'tolerance')]
    public ?string $tolerance = null;

    #[Url(as: 'run')]
    public ?int $runId = null;

    public function updated(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['productId', 'winnerStoreId', 'involvedStoreId', 'tolerance', 'runId']);
        $this->resetPage();
    }

    #[Computed]
    public function products()
    {
        return Product::orderBy('name')->get();
    }

    #[Computed]
    public function stores()
    {
        return Store::orderBy('code')->get();
    }

    #[Computed]
    public function tolerances()
    {
        return AllocationDecision::query()
            ->select('tolerance_percent')
            ->distinct()
            ->orderBy('tolerance_percent')
            ->pluck('tolerance_percent');
    }

    public function render()
    {
        $decisions = AllocationDecision::query()
            ->with(['product', 'winner'])
            ->withCount([
                'sellers as eligible_count' => fn ($q) => $q->where('price_guard_eligible', true),
            ])
            ->when($this->productId, fn ($q, $id) => $q->where('product_id', $id))
            ->when($this->winnerStoreId, fn ($q, $id) => $q->where('winner_store_id', $id))
            ->when($this->runId, fn ($q, $id) => $q->where('allocation_run_id', $id))
            ->when($this->tolerance !== null && $this->tolerance !== '', fn ($q) => $q->where('tolerance_percent', $this->tolerance))
            ->when($this->involvedStoreId, fn ($q, $id) => $q->whereHas('sellers', fn ($s) => $s->where('store_id', $id)))
            ->latest('id')
            ->paginate(25);

        return view('livewire.allocation-log.index', compact('decisions'))
            ->title('Allocation Logs — Allocation Engine Simulator');
    }
}
