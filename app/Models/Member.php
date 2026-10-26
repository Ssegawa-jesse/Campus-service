<?php

namespace App\Models;

use App\Support\LibrarySettings;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'member_no', 'type', 'programme', 'max_loans', 'expiry_date'];

    protected function casts(): array
    {
        return ['expiry_date' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function activeLoans(): HasMany
    {
        return $this->hasMany(Loan::class)->whereNull('returned_at');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function fines(): HasMany
    {
        return $this->hasMany(Fine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function loanLimit(): int
    {
        return $this->max_loans ?: LibrarySettings::int("max_loans_{$this->type}");
    }

    public function loanPeriodDays(): int
    {
        return LibrarySettings::int("loan_days_{$this->type}");
    }

    public function outstandingFines(): int
    {
        return (int) $this->fines()->where('status', 'unpaid')->sum('amount');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    /**
     * Reasons this member may not borrow right now (empty when they can).
     *
     * @return list<string>
     */
    public function borrowingBlocks(): array
    {
        $blocks = [];

        if (! $this->user->isActive()) {
            $blocks[] = 'Account is suspended.';
        }
        if ($this->isExpired()) {
            $blocks[] = 'Membership expired on '.$this->expiry_date->format('d M Y').'.';
        }
        if ($this->activeLoans()->count() >= $this->loanLimit()) {
            $blocks[] = "Borrowing limit of {$this->loanLimit()} books reached.";
        }
        $threshold = LibrarySettings::int('fine_block_threshold');
        $owed = $this->outstandingFines();
        if ($owed > 0 && $owed >= $threshold) {
            $blocks[] = 'Unpaid fines of UGX '.number_format($owed).' must be cleared first.';
        }
        if ($this->activeLoans()->where('due_at', '<', now())->exists()) {
            $blocks[] = 'Has overdue books that must be returned first.';
        }

        return $blocks;
    }

    public static function generateNumber(string $type): string
    {
        $prefix = $type === 'staff' ? 'STF' : 'STU';

        do {
            $number = $prefix.now()->format('y').str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        } while (static::where('member_no', $number)->exists());

        return $number;
    }
}
