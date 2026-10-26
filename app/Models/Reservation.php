<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $attributes = ['status' => 'waiting'];

    protected $fillable = ['book_id', 'member_id', 'copy_id', 'status', 'notified_at', 'expires_at'];

    protected function casts(): array
    {
        return ['notified_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function copy(): BelongsTo
    {
        return $this->belongsTo(Copy::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['waiting', 'ready'], true);
    }

    /** 1-based place in the waiting list for this title. */
    public function position(): ?int
    {
        if ($this->status !== 'waiting') {
            return null;
        }

        return static::where('book_id', $this->book_id)
            ->where('status', 'waiting')
            ->where(fn ($q) => $q->where('created_at', '<', $this->created_at)
                ->orWhere(fn ($q) => $q->where('created_at', $this->created_at)->where('id', '<', $this->id)))
            ->count() + 1;
    }
}
