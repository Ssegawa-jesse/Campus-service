<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViewLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'park_id', 'scene_id', 'tour_package_id', 'ip', 'created_at'];

    public function park(): BelongsTo
    {
        return $this->belongsTo(Park::class);
    }

    public function scene(): BelongsTo
    {
        return $this->belongsTo(Scene::class);
    }

    /** Record a view and bump the cached counter on the viewed record. */
    public static function record(?Park $park = null, ?Scene $scene = null, ?TourPackage $package = null): void
    {
        static::create([
            'user_id' => auth()->id(),
            'park_id' => $park?->id ?? $scene?->park_id ?? $package?->park_id,
            'scene_id' => $scene?->id,
            'tour_package_id' => $package?->id,
            'ip' => request()->ip(),
        ]);

        foreach ([$park, $scene, $package] as $model) {
            $model?->increment('views_count');
        }
    }
}
