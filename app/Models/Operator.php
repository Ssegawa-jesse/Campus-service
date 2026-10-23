<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Operator extends Model
{
    protected $fillable = [
        'user_id', 'company_name', 'slug', 'licence_no', 'contact_phone', 'contact_email', 'website',
        'address', 'about', 'logo', 'is_verified', 'status', 'approved_at', 'approved_by', 'rejection_reason',
    ];

    public const STATUSES = [
        'pending' => ['Pending approval', 'warning'],
        'approved' => ['Approved', 'success'],
        'rejected' => ['Rejected', 'danger'],
    ];

    protected function casts(): array
    {
        return ['is_verified' => 'boolean', 'approved_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        // Keep the old is_verified flag in step with the approval status.
        static::saving(function (Operator $operator) {
            $operator->status ??= $operator->is_verified ? 'approved' : 'pending';
            if ($operator->isDirty('status')) {
                $operator->is_verified = $operator->status === 'approved';
            } elseif ($operator->isDirty('is_verified')) {
                $operator->status = $operator->is_verified ? 'approved' : 'pending';
            }
        });
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? ucfirst((string) $this->status);
    }

    public function statusColor(): string
    {
        return self::STATUSES[$this->status][1] ?? 'secondary';
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parks(): BelongsToMany
    {
        return $this->belongsToMany(Park::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(TourPackage::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function logoUrl(): string
    {
        return media_url($this->logo, 'images/placeholder-operator.svg');
    }

    public function averageRating(): float
    {
        return round((float) $this->reviews()->where('is_approved', true)->avg('rating'), 1);
    }
}
