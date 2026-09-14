<?php

namespace App\Http\Controllers;

use App\Models\AllocationRun;
use Illuminate\Contracts\View\View;

class SimulationController extends Controller
{
    public function index(): View
    {
        $runs = AllocationRun::query()
            ->with('product')
            ->withCount('decisions')
            ->latest('id')
            ->paginate(25);

        return view('simulations.index', compact('runs'));
    }
}
