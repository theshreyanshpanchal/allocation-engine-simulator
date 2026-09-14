<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'operational_score',
        'distance_km',
        'status',
        'serviceable',
        'service_postcode_prefixes',
    ];

    protected function casts(): array
    {
        return [
            'operational_score' => 'float',
            'distance_km' => 'float',
            'serviceable' => 'boolean',
        ];
    }

    public function offers(): HasMany
    {
        return $this->hasMany(ProductOffer::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * A simplified stand-in for FR-RGN-001/002: does this store's service
     * area cover the buyer's postcode? A buyer postcode of null, or a store
     * with no prefixes configured, means "don't filter geographically" — so
     * existing stores/scenarios with no service area set stay unrestricted.
     */
    public function coversPostcode(?string $buyerPostcode): bool
    {
        if ($buyerPostcode === null || trim($buyerPostcode) === '') {
            return true;
        }

        $prefixes = $this->servicePostcodePrefixes();

        if ($prefixes === []) {
            return true;
        }

        $digits = preg_replace('/\D/', '', $buyerPostcode) ?? '';

        foreach ($prefixes as $prefix) {
            if ($prefix !== '' && str_starts_with($digits, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    public function servicePostcodePrefixes(): array
    {
        $raw = $this->service_postcode_prefixes;

        if ($raw === null || trim($raw) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    public function getScoreLabelAttribute(): string
    {
        return number_format($this->operational_score, 1);
    }

    public function getDistanceLabelAttribute(): string
    {
        $km = (float) $this->distance_km;

        return ($km == (int) $km ? (string) (int) $km : rtrim(rtrim(number_format($km, 2), '0'), '.')).' km';
    }
}
