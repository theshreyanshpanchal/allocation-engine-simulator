<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Contracts\View\View;

class StoreController extends Controller
{
    public function index(): View
    {
        $stores = Store::query()
            ->withCount('offers')
            ->orderBy('code')
            ->get();

        return view('stores.index', compact('stores'));
    }

    public function show(Store $store): View
    {
        $store->load(['offers.product']);

        return view('stores.show', compact('store'));
    }
}
