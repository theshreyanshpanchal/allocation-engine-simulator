<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $stores = Store::orderBy('code')->get();

        $products = Product::query()
            ->with(['offers.store'])
            ->orderBy('name')
            ->get();

        return view('products.index', compact('products', 'stores'));
    }

    public function show(Product $product): View
    {
        $offers = $product->offers()
            ->with('store')
            ->get()
            ->sortBy('store.code')
            ->values();

        return view('products.show', compact('product', 'offers'));
    }
}
