<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scene extends Model
{
    protected $fillable = [
        'park_id', 'title', 'image_path', 'description', 'initial_pitch', 'initial_yaw', 'audio_path', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['initial_pitch' => 'float', 'initial_yaw' => 'float'];
    }

    public function park(): BelongsTo
    {
        return $this->belongsTo(Park::class);
    }

    public function hotspots(): HasMany
    {
        return $this->hasMany(Hotspot::class);
    }

    public function imageUrl(): string
    {
        return media_url($this->image_path);
    }

    /** Scene definition in the format Pannellum's multi-scene "tour" config expects. */
    public function toPannellum(): array
    {
        return [
            'title' => $this->title,
            'type' => 'equirectangular',
            'panorama' => $this->imageUrl(),
            'pitch' => $this->initial_pitch,
            'yaw' => $this->initial_yaw,
            'hotSpots' => $this->hotspots->map(fn (Hotspot $h) => $h->toPannellum())->values()->all(),
        ];
    }
}
