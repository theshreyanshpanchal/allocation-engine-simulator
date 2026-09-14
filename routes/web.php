<?php

use App\Http\Controllers\AllocationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ScenarioController;
use App\Http\Controllers\SimulationController;
use App\Http\Controllers\StoreController;
use App\Livewire\AllocationLog\Index as AllocationLogIndex;
use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

Route::get('/', Dashboard::class)->name('dashboard');

Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
Route::get('/stores/{store}', [StoreController::class, 'show'])->name('stores.show');

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

Route::get('/allocations', AllocationLogIndex::class)->name('allocations.index');
Route::get('/allocations/{allocation}', [AllocationController::class, 'show'])->name('allocations.show');

Route::get('/simulations', [SimulationController::class, 'index'])->name('simulations.index');

Route::get('/scenarios', [ScenarioController::class, 'index'])->name('scenarios.index');
