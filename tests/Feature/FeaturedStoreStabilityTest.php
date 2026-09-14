<?php

use App\Allocation\AllocationEngine;
use App\Allocation\AllocationStateStore;
use App\Allocation\FeaturedStoreResolver;
use App\Livewire\Dashboard;
use App\Models\Product;
use App\Models\Store;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(DemoDataSeeder::class));

it('keeps the same featured store across reloads within a session (spec Test 6)', function () {
    $component = Livewire::test(Dashboard::class);
    $first = $component->instance()->featuredStore()->storeCode;

    expect($first)->toBe('STORE-A');

    foreach (range(1, 5) as $_) {
        $component->call('reevaluate');
        expect($component->instance()->featuredStore()->storeCode)->toBe($first);
    }

    // A brand-new mount in the SAME session still returns the cached pick.
    expect(Livewire::test(Dashboard::class)->instance()->featuredStore()->storeCode)->toBe($first);
});

it('is allowed to pick a different featured store when the tolerance changes the survivor set', function () {
    $component = Livewire::test(Dashboard::class);

    expect($component->instance()->featuredStore()->storeCode)->toBe('STORE-A');

    $component->set('tolerance', 2); // only Store C survives

    expect($component->instance()->featuredStore()->storeCode)->toBe('STORE-C');
});

it('re-picks a valid store if the cached featured store drops out of the survivor set', function () {
    $disc = Product::where('sku', 'BRAKE-DISC-001')->firstOrFail();
    $engine = app(AllocationEngine::class);
    $resolver = app(FeaturedStoreResolver::class);

    $storeAId = Store::where('code', 'STORE-A')->value('id');
    $evaluation2 = $engine->evaluate($disc, 2);

    // Poison the 2%-tolerance session key with Store A (which is excluded at 2%).
    session()->put($resolver->sessionKey($evaluation2), $storeAId);

    expect($resolver->resolve($evaluation2)->storeCode)->toBe('STORE-C');
});

it('persists realised allocation state per product and eligible context', function () {
    $disc = Product::where('sku', 'BRAKE-DISC-001')->firstOrFail();
    $store = app(AllocationStateStore::class);
    $evaluation = app(AllocationEngine::class)->evaluate($disc, 10);

    $ids = array_map(fn ($s) => $s->storeId, $evaluation->participating());
    $targets = [];
    foreach ($evaluation->participating() as $s) {
        $targets[$s->storeId] = $s->targetShare;
    }
    $winner = $evaluation->participating()[0]->storeId;

    $store->recordAllocation($disc->id, $evaluation->eligibleContextKey(), $winner, $targets);

    $state = $store->realisedState($disc->id, $evaluation->eligibleContextKey(), $ids);

    expect($evaluation->eligibleContextKey())->toBe('STORE-A|STORE-B|STORE-C')
        ->and($state->total)->toBe(1)
        ->and($state->countFor($winner))->toBe(1)
        ->and(App\Models\AllocationState::where('product_id', $disc->id)
            ->where('eligible_context', $evaluation->eligibleContextKey())
            ->count())->toBe(3);
});
