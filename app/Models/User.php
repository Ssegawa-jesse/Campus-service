<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_TOURIST = 'tourist';
    public const ROLE_OPERATOR = 'operator';

    protected $fillable = [
        'name', 'email', 'phone', 'country', 'avatar', 'password', 'role', 'is_active', 'sms_opt_in',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = ['role' => self::ROLE_TOURIST, 'is_active' => true, 'sms_opt_in' => true];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'sms_opt_in' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isTourist(): bool
    {
        return $this->role === self::ROLE_TOURIST;
    }

    public function isOperator(): bool
    {
        return $this->role === self::ROLE_OPERATOR;
    }

    public function dashboardRoute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'admin.dashboard',
            self::ROLE_OPERATOR => 'operator.dashboard',
            default => 'tourist.dashboard',
        };
    }

    public function operator(): HasOne
    {
        return $this->hasOne(Operator::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'tourist_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function tripPlans(): HasMany
    {
        return $this->hasMany(TripPlan::class);
    }

    public function favourites(): BelongsToMany
    {
        return $this->belongsToMany(Park::class, 'favourites')->withTimestamps();
    }
}
