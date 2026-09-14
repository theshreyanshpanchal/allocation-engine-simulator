<x-layouts.app title="Simulation Runs — Allocation Engine Simulator">
    <div class="mb-6">
        <p class="page-eyebrow">// Audit trail</p>
        <h1 class="page-title">Allocation Logs</h1>
        <p class="page-subtitle">What decisions did the system make previously, and why?</p>
    </div>

    @include('allocations._subnav', ['current' => 'runs'])

    <div class="table-shell">
        <table class="table-base">
            <thead class="table-head">
                <tr>
                    <th>Run</th>
                    <th>Started</th>
                    <th>Product</th>
                    <th class="text-right">Tolerance</th>
                    <th class="text-right">Purchases</th>
                    <th class="text-right">Decisions</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($runs as $run)
                    <tr>
                        <td class="font-mono text-xs text-ink-300">{{ $run->reference }}</td>
                        <td class="text-ink-300">{{ optional($run->started_at)->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $run->product->name }}</td>
                        <td class="text-right font-mono tabular-nums">{{ num($run->tolerance_percent) }}%</td>
                        <td class="text-right font-mono tabular-nums">{{ number_format($run->purchase_count) }}</td>
                        <td class="text-right font-mono tabular-nums">{{ number_format($run->decisions_count) }}</td>
                        <td class="text-right">
                            <a href="{{ route('allocations.index', ['run' => $run->id]) }}" class="font-medium text-brand-300 hover:text-brand-200">
                                View decisions
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-ink-400">
                            No simulation runs yet. Run one from the Dashboard.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $runs->links() }}</div>
</x-layouts.app>
