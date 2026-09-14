<x-layouts.app title="Products — Allocation Engine Simulator">
    <div class="mb-6">
        <p class="page-eyebrow">// Catalog</p>
        <h1 class="page-title">Products</h1>
        <p class="page-subtitle">What products are available and what offers does each store have?</p>
    </div>

    <div class="table-shell">
        <table class="table-base">
            <thead class="table-head">
                <tr>
                    <th>Product</th>
                    <th>Vehicle</th>
                    @foreach ($stores as $store)
                        <th class="text-right">{{ $store->name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($products as $product)
                    @php $byStore = $product->offers->keyBy('store_id'); @endphp
                    <tr>
                        <td>
                            <a href="{{ route('products.show', $product) }}" class="font-medium text-brand-300 hover:text-brand-200">
                                {{ $product->name }}
                            </a>
                        </td>
                        <td class="text-ink-400">{{ $product->vehicle_label }}</td>
                        @foreach ($stores as $store)
                            <td class="text-right">
                                <x-money :amount="optional($byStore->get($store->id))->price" />
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.app>
