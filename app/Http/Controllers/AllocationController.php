<?php

namespace App\Http\Controllers;

use App\Allocation\AllocationExplanationService;
use App\Models\AllocationDecision;
use Illuminate\Contracts\View\View;

class AllocationController extends Controller
{
    public function show(AllocationDecision $allocation, AllocationExplanationService $explanations): View
    {
        $allocation->load(['product', 'winner', 'run', 'sellers.store']);

        $sellers = $allocation->sellers
            ->sortBy('store.code')
            ->values();

        return view('allocations.show', [
            'decision' => $allocation,
            'sellers' => $sellers,
            'explanation' => $explanations->forDecision($allocation),
        ]);
    }
}
