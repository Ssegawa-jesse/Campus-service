<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A point on the Smart Navigation map (gate, jetty, trail, airfield, attraction, lodge…). */
class Place extends Model
{
    protected $fillable = ['name', 'slug', 'type', 'zone', 'latitude', 'longitude', 'description', 'tip', 'opening_hours', 'is_active'];

    public const TYPES = [
        'gate' => ['Park gate', 'door-open', '#b45309'],
        'jetty' => ['Jetty / ferry', 'water', '#0369a1'],
        'trail' => ['Trail', 'signpost-split', '#15803d'],
        'airfield' => ['Airfield', 'airplane', '#7c3aed'],
        'attraction' => ['Attraction', 'star', '#dc2626'],
        'forest' => ['Forest', 'tree', '#166534'],
        'lodge' => ['Accommodation', 'house', '#a16207'],
        'town' => ['Town', 'shop', '#475569'],
    ];

    public const ZONES = ['south' => 'South bank', 'river' => 'Victoria Nile', 'north' => 'North bank', 'route' => 'On the way'];

    protected function casts(): array
    {
        return ['latitude' => 'float', 'longitude' => 'float', 'is_active' => 'boolean'];
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type][0] ?? ucfirst($this->type);
    }

    public function toMap(): array
    {
        return [
            'id' => $this->id, 'slug' => $this->slug, 'name' => $this->name, 'type' => $this->type,
            'typeLabel' => $this->typeLabel(), 'icon' => self::TYPES[$this->type][1] ?? 'geo-alt',
            'color' => self::TYPES[$this->type][2] ?? '#334155', 'zone' => $this->zone,
            'lat' => $this->latitude, 'lng' => $this->longitude, 'description' => $this->description,
            'tip' => $this->tip, 'hours' => $this->opening_hours,
        ];
    }
}
