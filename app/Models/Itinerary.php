<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** A saved Smart Itinerary (input preferences + the generated day-by-day plan and budget). */
class Itinerary extends Model
{
    protected $fillable = ['reference', 'user_id', 'title', 'start_date', 'days', 'visitor_category', 'adults', 'children', 'input', 'plan', 'total_ugx', 'status'];

    protected function casts(): array
    {
        return ['input' => 'array', 'plan' => 'array', 'start_date' => 'date'];
    }

    protected static function booted(): void
    {
        static::creating(fn (Itinerary $i) => $i->reference ??= 'ITN-'.strtoupper(Str::random(6)));
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
