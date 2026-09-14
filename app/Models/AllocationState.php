<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllocationState extends Model
{
    protected $table = 'allocation_state';

    protected $fillable = [
        'product_id',
        'store_id',
        'eligible_context',
        'allocated_count',
        'total_count',
        'realised_share',
        'target_share',
    ];

    protected function casts(): array
    {
        return [
            'allocated_count' => 'integer',
            'total_count' => 'integer',
            'realised_share' => 'float',
            'target_share' => 'float',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
