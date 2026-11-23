<?php

namespace App\Models;

use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    /** @use HasFactory<ReservationFactory> */
    use HasFactory;

    /**
     * Programmes offered in the reservation form.
     *
     * @var list<string>
     */
    public const PROGRAMMES = [
        'Bachelor of Business Computing',
        'Bachelor of Business Administration',
        'Bachelor of Commerce',
        'Bachelor of Science in Accounting',
        'Bachelor of Procurement and Supply Chain Management',
        'Other',
    ];

    /**
     * Columns that can be filled from the reservation form.
     * These match the form field names and the migration columns.
     *
     * @var list<string>
     */
    protected $fillable = [
        'student_name',
        'registration_number',
        'programme',
        'book_title',
        'pickup_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pickup_date' => 'date',
        ];
    }
}
