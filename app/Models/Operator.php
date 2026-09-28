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
        'address', 'about', 'logo', 'is_verified',
    ];

    protected function casts(): array
    {
        return ['is_verified' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
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
