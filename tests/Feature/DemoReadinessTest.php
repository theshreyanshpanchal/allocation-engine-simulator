<?php

use App\Livewire\Dashboard;
use App\Models\Product;
use App\Models\ProductOffer;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(DemoDataSeeder::class));

it('opens on the dashboard with the Front Brake Disc and 10% tolerance', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Allocation Simulator')
        ->assertSee('Front Brake Disc');
});

it('offers a dismissible zero-tolerance interaction with both choices', function () {
    Livewire::test(Dashboard::class)
        ->set('tolerance', 8)
        ->set('tolerance', 0)
        ->assertSee('Keep 0%')
        ->assertSee('Return to previous value')
        ->call('restorePreviousTolerance')
        ->assertSet('tolerance', 8.0);
});

it('collapses the warning to a slim banner once 0% is acknowledged', function () {
    Livewire::test(Dashboard::class)
        ->set('tolerance', 0)
        ->call('keepZeroTolerance')
        ->assertDontSee('Return to previous value')
        ->assertSee('Zero tolerance active');
});

it('shows a friendly message when no store can be allocated', function () {
    ProductOffer::query()->update(['stock_quantity' => 0]);

    Livewire::test(Dashboard::class)
        ->assertSee('No allocation is possible right now')
        ->assertSee('out of stock')
        ->assertDontSee('Exception');
});

it('notes when a single store takes the whole allocation', function () {
    Livewire::test(Dashboard::class)
        ->set('tolerance', 2)
        ->assertSee('Only one store is inside the price guard');
});

it('shows empty states on the log screens before any run', function () {
    $this->get('/allocations')->assertOk()->assertSee('No allocation decisions yet');
    $this->get('/simulations')->assertOk()->assertSee('No simulation runs yet');
});

it('serves every screen without a server error', function () {
    $product = Product::first();

    foreach ([
        '/', '/stores', "/stores/{$product->offers->first()->store_id}",
        '/products', "/products/{$product->id}",
        '/allocations', '/simulations', '/scenarios',
    ] as $path) {
        $this->get($path)->assertOk()->assertDontSee('Whoops');
    }
});

it('exposes the atlas:reset command', function () {
    expect(Artisan::all())->toHaveKey('atlas:reset');
});
