<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Booking extends Model
{
    public const STATUSES = [
        'pending' => ['Awaiting operator', 'secondary'],
        'quoted' => ['Quote sent – pay now', 'info'],
        'paid' => ['Paid', 'primary'],
        'confirmed' => ['Confirmed', 'success'],
        'completed' => ['Completed', 'dark'],
        'declined' => ['Declined', 'danger'],
        'cancelled' => ['Cancelled', 'warning'],
    ];

    protected $fillable = [
        'reference', 'tourist_id', 'operator_id', 'park_id', 'tour_package_id', 'travel_date', 'group_size',
        'contact_phone', 'message', 'status', 'quoted_amount', 'operator_response', 'responded_at',
    ];

    protected function casts(): array
    {
        return ['travel_date' => 'date', 'responded_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            $booking->reference ??= 'P360-'.strtoupper(Str::random(6));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function tourist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tourist_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function park(): BelongsTo
    {
        return $this->belongsTo(Park::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(TourPackage::class, 'tour_package_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? ucfirst($this->status);
    }

    public function statusColor(): string
    {
        return self::STATUSES[$this->status][1] ?? 'secondary';
    }

    public function canBePaid(): bool
    {
        return $this->status === 'quoted' && $this->quoted_amount > 0;
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['paid', 'confirmed', 'completed'], true);
    }
}
