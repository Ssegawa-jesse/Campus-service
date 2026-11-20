<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourPackage extends Model
{
    protected $fillable = [
        'operator_id', 'park_id', 'title', 'slug', 'description', 'includes', 'days', 'price_ugx',
        'max_group', 'image', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function park(): BelongsTo
    {
        return $this->belongsTo(Park::class);
    }

    public function imageUrl(): string
    {
        return $this->image ? media_url($this->image) : ($this->park?->coverUrl() ?? asset('images/placeholder-park.svg'));
    }
}
