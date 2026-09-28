<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    protected $fillable = [
        'park_id', 'name', 'category', 'fitness_level', 'duration', 'indicative_cost', 'description', 'image',
    ];

    public const CATEGORIES = [
        'primates' => 'Primate tracking',
        'game_drive' => 'Game drive',
        'boat' => 'Boat trip',
        'hiking' => 'Hiking / climbing',
        'birding' => 'Birding',
        'culture' => 'Cultural experience',
        'nature' => 'Nature walk',
    ];

    public const FITNESS = [1 => 'Low', 2 => 'Medium', 3 => 'High'];

    public function park(): BelongsTo
    {
        return $this->belongsTo(Park::class);
    }

    public function fitnessLabel(): string
    {
        return self::FITNESS[$this->fitness_level] ?? 'Low';
    }
}
