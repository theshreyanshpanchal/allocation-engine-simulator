<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AllocationRun extends Model
{
    protected $fillable = [
        'product_id',
        'buyer_postcode',
        'tolerance_percent',
        'purchase_count',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'tolerance_percent' => 'float',
            'purchase_count' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function getReferenceAttribute(): string
    {
        return 'RUN-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(AllocationDecision::class);
    }
}
