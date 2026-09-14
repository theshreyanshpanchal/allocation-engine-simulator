<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllocationDecisionSeller extends Model
{
    protected $fillable = [
        'allocation_decision_id',
        'store_id',
        'price',
        'score',
        'distance_km',
        'stock_available',
        'serviceable',
        'price_guard_eligible',
        'exclusion_reason',
        'target_share',
        'is_winner',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'score' => 'float',
            'distance_km' => 'float',
            'stock_available' => 'boolean',
            'serviceable' => 'boolean',
            'price_guard_eligible' => 'boolean',
            'target_share' => 'float',
            'is_winner' => 'boolean',
        ];
    }

    public function decision(): BelongsTo
    {
        return $this->belongsTo(AllocationDecision::class, 'allocation_decision_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
