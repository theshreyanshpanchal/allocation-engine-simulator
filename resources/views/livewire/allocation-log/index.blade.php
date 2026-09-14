<div>
    <div class="mb-6">
        <p class="page-eyebrow">// Audit trail</p>
        <h1 class="page-title">Allocation Logs</h1>
        <p class="page-subtitle">What decisions did the system make previously, and why?</p>
    </div>

    @include('allocations._subnav', ['current' => 'decisions'])

    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <label class="block">
            <span class="field-label">Product</span>
            <select wire:model.live="productId" class="field-control field-select">
                <option value="">All products</option>
                @foreach ($this->products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="block">
            <span class="field-label">Winner</span>
            <select wire:model.live="winnerStoreId" class="field-control field-select">
                <option value="">Any winner</option>
                @foreach ($this->stores as $store)
                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="block">
            <span class="field-label">Involved store</span>
            <select wire:model.live="involvedStoreId" class="field-control field-select">
                <option value="">Any store</option>
                @foreach ($this->stores as $store)
                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="block">
            <span class="field-label">Tolerance</span>
            <select wire:model.live="tolerance" class="field-control field-select">
                <option value="">Any tolerance</option>
                @foreach ($this->tolerances as $value)
                    <option value="{{ $value }}">{{ num($value) }}%</option>
                @endforeach
            </select>
        </label>
    </div>

    @if ($this->runId)
        <p class="mb-3 text-sm text-ink-300">
            Filtered to run <span class="font-mono text-ink-100">RUN-{{ str_pad($this->runId, 4, '0', STR_PAD_LEFT) }}</span>.
            <button wire:click="clearFilters" class="font-medium text-brand-300 hover:text-brand-200">Clear</button>
        </p>
    @endif

    <div class="table-shell">
        <table class="table-base">
            <thead class="table-head">
                <tr>
                    <th>Decision</th>
                    <th>Time</th>
                    <th>Product</th>
                    <th class="text-right">Tolerance</th>
                    <th>Winner</th>
                    <th class="text-right">Eligible</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($decisions as $decision)
                    <tr>
                        <td class="font-mono text-xs text-ink-300">{{ $decision->reference }}</td>
                        <td class="text-ink-300">{{ $decision->created_at->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $decision->product->name }}</td>
                        <td class="text-right font-mono tabular-nums">{{ num($decision->tolerance_percent) }}%</td>
                        <td class="font-medium text-ink-50">{{ $decision->winner?->name ?? '—' }}</td>
                        <td class="text-right font-mono tabular-nums">{{ $decision->eligible_count }}</td>
                        <td class="text-right">
                            <a href="{{ route('allocations.show', $decision) }}" class="font-medium text-brand-300 hover:text-brand-200">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-ink-400">
                            No allocation decisions yet. Run a simulation from the Dashboard.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $decisions->links() }}</div>
</div>
