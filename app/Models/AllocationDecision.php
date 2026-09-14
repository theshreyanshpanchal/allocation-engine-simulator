<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AllocationDecision extends Model
{
    protected $fillable = [
        'allocation_run_id',
        'product_id',
        'winner_store_id',
        'purchase_index',
        'buyer_postcode',
        'tolerance_percent',
        'lowest_price',
        'price_ceiling',
        'eligible_context',
        'decision_reason',
        'human_explanation',
        'technical_explanation',
    ];

    protected function casts(): array
    {
        return [
            'tolerance_percent' => 'float',
            'lowest_price' => 'float',
            'price_ceiling' => 'float',
            'purchase_index' => 'integer',
        ];
    }

    public function getReferenceAttribute(): string
    {
        return 'ALLOC-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(AllocationRun::class, 'allocation_run_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'winner_store_id');
    }

    public function sellers(): HasMany
    {
        return $this->hasMany(AllocationDecisionSeller::class);
    }
}
