<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Park extends Model
{
    protected $fillable = [
        'name', 'slug', 'type', 'region', 'tagline', 'description', 'how_to_get_there', 'best_time',
        'best_months', 'interests', 'difficulty_level', 'min_cost', 'min_days', 'area_km2',
        'distance_from_kampala_km', 'established', 'latitude', 'longitude', 'cover_image', 'video_url',
        'is_unesco', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'best_months' => 'array',
            'interests' => 'array',
            'is_unesco' => 'boolean',
            'is_published' => 'boolean',
            'min_cost' => 'decimal:2',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /** Interest tags the trip planner and admin forms understand. */
    public const INTERESTS = [
        'gorillas' => 'Gorilla trekking',
        'chimpanzees' => 'Chimpanzee tracking',
        'primates' => 'Other primates',
        'big_game' => 'Big game / game drives',
        'birding' => 'Bird watching',
        'hiking' => 'Hiking & mountains',
        'boat' => 'Boat cruises',
        'waterfalls' => 'Waterfalls',
        'culture' => 'Culture & communities',
        'hot_springs' => 'Hot springs & geology',
        'walking_safari' => 'Walking / horseback safaris',
        'remote' => 'Remote wilderness',
    ];

    public const TYPES = [
        'national_park' => 'National Park',
        'wildlife_reserve' => 'Wildlife Reserve',
        'sanctuary' => 'Sanctuary',
    ];

    public const DIFFICULTY = [1 => 'Easy', 2 => 'Moderate', 3 => 'Challenging'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function scenes(): HasMany
    {
        return $this->hasMany(Scene::class)->orderBy('sort_order');
    }

    public function media(): HasMany
    {
        return $this->hasMany(ParkMedia::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(TourPackage::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function operators(): BelongsToMany
    {
        return $this->belongsToMany(Operator::class);
    }

    public function coverUrl(): string
    {
        return media_url($this->cover_image, 'images/placeholder-park.svg');
    }

    public function difficultyLabel(): string
    {
        return self::DIFFICULTY[$this->difficulty_level] ?? 'Moderate';
    }

    public function interestLabels(): array
    {
        return collect($this->interests ?? [])->map(fn ($i) => self::INTERESTS[$i] ?? $i)->all();
    }

    public function averageRating(): float
    {
        return round((float) $this->reviews()->where('is_approved', true)->avg('rating'), 1);
    }
}
