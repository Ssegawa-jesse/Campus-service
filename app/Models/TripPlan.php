<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripPlan extends Model
{
    protected $fillable = ['user_id', 'title', 'preferences', 'recommended_parks'];

    protected function casts(): array
    {
        return ['preferences' => 'array', 'recommended_parks' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
