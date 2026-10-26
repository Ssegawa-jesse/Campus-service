<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const METHODS = ['mtn' => 'MTN Mobile Money', 'airtel' => 'Airtel Money', 'card' => 'Card', 'cash' => 'Cash'];

    protected $fillable = ['fine_id', 'member_id', 'amount', 'method', 'phone', 'reference', 'gateway_ref', 'status', 'paid_at', 'recorded_by'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }

    public function fine(): BelongsTo
    {
        return $this->belongsTo(Fine::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? $this->method;
    }
}
