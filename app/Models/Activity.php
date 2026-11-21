<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A Murchison Falls activity with UWA tiered pricing and the scheduling rules
 * the Smart Itinerary engine uses (time slots, hours, interests, priority).
 */
class Activity extends Model
{
    protected $fillable = [
        'park_id', 'place_id', 'name', 'slug', 'category', 'fitness_level', 'duration', 'indicative_cost', 'description', 'image',
        'price_fnr_usd', 'price_fr_usd', 'price_eac_ugx', 'group_fee_usd', 'group_fee_ugx', 'hours', 'slots', 'interests',
        'requires', 'priority', 'min_age', 'wet_season_note', 'is_active',
    ];

    public const CATEGORIES = [
        'boat' => 'Boat trip',
        'game_drive' => 'Game drive',
        'hiking' => 'Hiking',
        'primates' => 'Primate tracking',
        'birding' => 'Birding',
        'fishing' => 'Sport fishing',
        'nature' => 'Nature walk',
    ];

    public const FITNESS = [1 => 'Easy', 2 => 'Moderate', 3 => 'Demanding'];

    protected function casts(): array
    {
        return [
            'slots' => 'array', 'interests' => 'array', 'requires' => 'array', 'is_active' => 'boolean',
            'price_fnr_usd' => 'float', 'price_fr_usd' => 'float', 'group_fee_usd' => 'float', 'hours' => 'float',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function park(): BelongsTo
    {
        return $this->belongsTo(Park::class);
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function scenes(): HasMany
    {
        return $this->hasMany(Scene::class)->orderBy('sort_order');
    }

    public function media(): HasMany
    {
        return $this->hasMany(ParkMedia::class)->orderBy('sort_order');
    }

    public function fitnessLabel(): string
    {
        return self::FITNESS[$this->fitness_level] ?? 'Easy';
    }

    /** Per-person price for a UWA visitor category, in that category's own currency. */
    public function priceFor(string $category): float
    {
        return match ($category) {
            'fr' => $this->price_fr_usd,
            'eac' => (float) $this->price_eac_ugx,
            default => $this->price_fnr_usd,
        };
    }

    /** Per-person price converted to UGX (USD categories × exchange rate). */
    public function priceUgx(string $category): int
    {
        return $category === 'eac' ? (int) $this->price_eac_ugx : usd_to_ugx($this->priceFor($category));
    }

    public function groupFeeUgx(string $category): int
    {
        return $category === 'eac' ? (int) $this->group_fee_ugx : usd_to_ugx($this->group_fee_usd);
    }

    public function priceLabel(string $category): string
    {
        $p = $this->priceFor($category);
        return $category === 'eac' ? 'UGX '.number_format($p) : 'US$'.number_format($p, $p == (int) $p ? 0 : 2);
    }

    public function needsFerry(): bool
    {
        return $this->place?->zone === 'north';
    }
}
