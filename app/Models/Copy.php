<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Copy extends Model
{
    use HasFactory;

    public const STATUSES = ['available', 'on_loan', 'on_hold', 'lost', 'damaged'];

    public const CONDITIONS = ['new', 'good', 'fair', 'poor'];

    protected $attributes = ['status' => 'available', 'condition' => 'good'];

    protected $fillable = ['book_id', 'code', 'shelf_location', 'condition', 'status'];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function currentLoan(): HasOne
    {
        return $this->hasOne(Loan::class)->whereNull('returned_at')->latestOfMany();
    }

    public static function generateCode(): string
    {
        do {
            $code = 'BK-'.strtoupper(Str::random(8));
        } while (static::where('code', $code)->exists());

        return $code;
    }
}
