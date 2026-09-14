<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'brand',
        'vehicle_make',
        'vehicle_model',
        'vehicle_year',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'vehicle_year' => 'integer',
        ];
    }

    public function offers(): HasMany
    {
        return $this->hasMany(ProductOffer::class);
    }

    public function getVehicleLabelAttribute(): string
    {
        return "{$this->vehicle_make} {$this->vehicle_model} {$this->vehicle_year}";
    }
}
