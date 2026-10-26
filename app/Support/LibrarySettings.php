<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

/**
 * Library rules that the administrator can change from the Settings page.
 */
class LibrarySettings
{
    public const DEFAULTS = [
        'library_name' => 'MUBS Library',
        'loan_days_student' => 14,
        'loan_days_staff' => 30,
        'max_loans_student' => 3,
        'max_loans_staff' => 5,
        'max_renewals' => 2,
        'fine_per_day' => 500,
        'fine_max_per_loan' => 20000,
        'fine_block_threshold' => 5000,
        'reservation_hold_days' => 3,
        'reminder_days_before' => 2,
    ];

    public const LABELS = [
        'library_name' => 'Library name',
        'loan_days_student' => 'Loan period for students (days)',
        'loan_days_staff' => 'Loan period for staff (days)',
        'max_loans_student' => 'Maximum books a student may borrow',
        'max_loans_staff' => 'Maximum books a staff member may borrow',
        'max_renewals' => 'Maximum renewals per loan',
        'fine_per_day' => 'Overdue fine per day (UGX)',
        'fine_max_per_loan' => 'Maximum fine per loan (UGX, 0 = no cap)',
        'fine_block_threshold' => 'Block borrowing when unpaid fines reach (UGX)',
        'reservation_hold_days' => 'Days a reserved copy is held for collection',
        'reminder_days_before' => 'Send due-date reminder this many days before',
    ];

    protected static ?array $cache = null;

    public static function all(): array
    {
        if (static::$cache === null) {
            $stored = Schema::hasTable('settings') ? Setting::pluck('value', 'key')->all() : [];
            static::$cache = array_merge(static::DEFAULTS, $stored);
        }

        return static::$cache;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::all()[$key] ?? $default;
    }

    public static function int(string $key): int
    {
        return (int) static::get($key, 0);
    }

    public static function set(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        static::flush();
    }

    public static function flush(): void
    {
        static::$cache = null;
    }
}
