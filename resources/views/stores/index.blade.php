<x-layouts.app title="Stores — Allocation Engine Simulator">
    <div class="mb-6">
        <p class="page-eyebrow">// Network</p>
        <h1 class="page-title">Stores</h1>
        <p class="page-subtitle">Who are the sellers? Reference data for the simulator.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($stores as $store)
            <div class="card card-hover flex flex-col p-5">
                <div class="flex items-start justify-between">
                    <h2 class="text-base font-semibold text-ink-50">{{ $store->name }}</h2>
                    <x-badge :tone="$store->isActive() ? 'success' : 'neutral'">
                        {{ ucfirst($store->status) }}
                    </x-badge>
                </div>
                <p class="mt-0.5 font-mono text-xs uppercase tracking-widest text-ink-400">{{ $store->code }}</p>

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-ink-400">Operational score</dt>
                        <dd class="font-mono font-medium text-ink-50">{{ $store->score_label }} / 5</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-400">Distance</dt>
                        <dd class="font-mono font-medium text-ink-50">{{ $store->distance_label }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-400">Products</dt>
                        <dd class="font-mono font-medium text-ink-50">{{ $store->offers_count }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-400">Serviceability</dt>
                        <dd class="font-medium text-ink-50">{{ $store->serviceable ? 'Serviceable' : 'Not serviceable' }}</dd>
                    </div>
                </dl>

                <a href="{{ route('stores.show', $store) }}" class="btn-secondary btn-sm mt-5">
                    View details
                </a>
            </div>
        @endforeach
    </div>

    <div class="mt-8">
        <x-store-glossary />
    </div>
</x-layouts.app>
