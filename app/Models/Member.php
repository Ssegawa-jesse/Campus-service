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

    protected $fillable = ['user_id', 'member_no', 'type', 'programme', 'date_of_birth', 'year_of_study', 'max_loans', 'expiry_date'];

    protected function casts(): array
    {
        return ['expiry_date' => 'date', 'date_of_birth' => 'date'];
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

    public function age(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function yearLabel(): ?string
    {
        if (! $this->year_of_study) {
            return null;
        }

        $suffix = match ($this->year_of_study) {
            1 => 'st',
            2 => 'nd',
            3 => 'rd',
            default => 'th',
        };

        return "Year {$this->year_of_study} ({$this->year_of_study}{$suffix} year)";
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

    /** Validation rules for the details shown on the library card. */
    public static function profileRules(bool $required = true): array
    {
        $need = $required ? 'required' : 'nullable';

        return [
            'programme' => [$need, 'string', 'max:255'],
            'date_of_birth' => [$need, 'date', 'before:-10 years', 'after:1930-01-01'],
            'year_of_study' => [$required ? 'required_if:type,student' : 'nullable', 'nullable', 'integer', 'between:1,7'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ];
    }

    public static function profileMessages(): array
    {
        return [
            'date_of_birth.before' => 'Please enter a valid date of birth.',
            'year_of_study.required_if' => 'Please select your year of study.',
            'photo.max' => 'The profile picture may not be bigger than 3 MB.',
        ];
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
