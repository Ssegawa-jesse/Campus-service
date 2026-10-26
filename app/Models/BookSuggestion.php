<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BookSuggestion extends Model
{
    public const STATUSES = [
        'pending' => 'Pending review',
        'approved' => 'Approved',
        'ordered' => 'Ordered',
        'added' => 'Now in library',
        'rejected' => 'Not approved',
    ];

    protected $fillable = ['user_id', 'book_id', 'title', 'author', 'isbn', 'course', 'reason', 'status', 'response', 'responded_by', 'votes_count'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function voters(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'suggestion_votes')->withPivot('created_at');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusColour(): string
    {
        return match ($this->status) {
            'approved', 'ordered' => 'info',
            'added' => 'success',
            'rejected' => 'secondary',
            default => 'warning',
        };
    }
}
