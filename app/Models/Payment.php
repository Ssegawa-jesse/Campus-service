<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const METHODS = [
        'mobile_money_mtn' => 'MTN Mobile Money',
        'mobile_money_airtel' => 'Airtel Money',
        'card' => 'Visa / Mastercard',
    ];

    protected $fillable = [
        'booking_id', 'user_id', 'tx_ref', 'gateway', 'method', 'phone', 'amount', 'currency', 'status',
        'gateway_reference', 'meta', 'paid_at',
    ];

    protected function casts(): array
    {
        return ['meta' => 'array', 'paid_at' => 'datetime'];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? ucfirst((string) $this->method);
    }
}
