<?php

namespace App\Http\Controllers;

use App\Allocation\ScenarioCatalog;
use Illuminate\Contracts\View\View;

class ScenarioController extends Controller
{
    public function index(): View
    {
        $scenarios = app(ScenarioCatalog::class)->all();

        return view('scenarios.index', compact('scenarios'));
    }
}
