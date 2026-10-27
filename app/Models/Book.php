<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Book extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'isbn', 'publisher', 'year', 'edition', 'category_id', 'subject', 'description', 'cover_image'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class);
    }

    public function copies(): HasMany
    {
        return $this->hasMany(Copy::class);
    }

    public function availableCopies(): HasMany
    {
        return $this->hasMany(Copy::class)->where('status', 'available');
    }

    public function loans(): HasManyThrough
    {
        return $this->hasManyThrough(Loan::class, Copy::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function waitingReservations(): HasMany
    {
        return $this->hasMany(Reservation::class)->where('status', 'waiting')->orderBy('created_at');
    }

    public function suggestions(): HasMany
    {
        return $this->hasMany(BookSuggestion::class);
    }

    public function authorNames(): string
    {
        return $this->authors->pluck('name')->join(', ');
    }

    public function coverUrl(): ?string
    {
        return $this->cover_image ? media_url($this->cover_image) : null;
    }

    public function shelfLocations(): string
    {
        return $this->copies->pluck('shelf_location')->filter()->unique()->join(', ');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.trim($term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('title', 'like', $like)
                ->orWhere('isbn', 'like', $like)
                ->orWhere('subject', 'like', $like)
                ->orWhere('publisher', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhereHas('authors', fn (Builder $a) => $a->where('name', 'like', $like))
                ->orWhereHas('category', fn (Builder $c) => $c->where('name', 'like', $like));
        });
    }
}
