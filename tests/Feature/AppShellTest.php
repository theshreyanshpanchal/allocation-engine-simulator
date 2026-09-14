<?php

use App\Livewire\Dashboard;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(DemoDataSeeder::class));

it('renders the dashboard as a Livewire component at the root route', function () {
    $this->get('/')
        ->assertOk()
        ->assertSeeLivewire(Dashboard::class);
});

it('mounts the Dashboard Livewire component', function () {
    Livewire::test(Dashboard::class)->assertOk();
});

it('serves every primary nav destination', function () {
    foreach (['/', '/stores', '/products', '/allocations'] as $path) {
        $this->get($path)->assertOk();
    }
});

it('shows the four navigation items on every page', function () {
    foreach (['/', '/stores', '/products', '/allocations'] as $path) {
        $this->get($path)
            ->assertSee('Dashboard')
            ->assertSee('Stores')
            ->assertSee('Products')
            ->assertSee('Allocation Logs');
    }
});

it('marks the current nav item as active', function () {
    $this->get('/stores')->assertSee('aria-current="page"', false);
});

it('references locally-built assets and no external hosts', function () {
    $html = $this->get('/')->getContent();

    expect($html)->toContain('/build/assets/');

    preg_match_all('#https?://(?!127\.0\.0\.1|localhost)[^"\'\s]+#', $html, $matches);
    expect($matches[0])->toBe([]);
});
