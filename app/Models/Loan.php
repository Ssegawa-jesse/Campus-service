<?php

namespace App\Models;

use App\Support\LibrarySettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Loan extends Model
{
    protected $fillable = [
        'copy_id', 'member_id', 'issued_by', 'received_by', 'issued_at', 'due_at', 'returned_at',
        'renewals', 'reminder_sent_at', 'overdue_notice_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'due_at' => 'datetime',
            'returned_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'overdue_notice_sent_at' => 'datetime',
        ];
    }

    public function copy(): BelongsTo
    {
        return $this->belongsTo(Copy::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function fine(): HasOne
    {
        return $this->hasOne(Fine::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('returned_at');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNull('returned_at')->where('due_at', '<', now());
    }

    public function isOverdue(): bool
    {
        return $this->returned_at === null && $this->due_at->isPast();
    }

    /** Whole days late as of the given moment (or the return date). */
    public function daysOverdue(?Carbon $at = null): int
    {
        $end = ($this->returned_at ?? $at ?? now())->copy()->startOfDay();
        $due = $this->due_at->copy()->startOfDay();

        return $end->greaterThan($due) ? (int) $due->diffInDays($end) : 0;
    }

    public function calculateFine(?Carbon $at = null): int
    {
        $days = $this->daysOverdue($at);
        if ($days === 0) {
            return 0;
        }

        $amount = $days * LibrarySettings::int('fine_per_day');
        $cap = LibrarySettings::int('fine_max_per_loan');

        return $cap > 0 ? min($amount, $cap) : $amount;
    }
}
