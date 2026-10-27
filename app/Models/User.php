<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLES = ['admin', 'librarian', 'member'];

    protected $attributes = ['role' => 'member', 'status' => 'active'];

    protected $fillable = ['name', 'email', 'phone', 'photo', 'password', 'role', 'status'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    public function suggestions(): HasMany
    {
        return $this->hasMany(BookSuggestion::class);
    }

    public function photoUrl(): ?string
    {
        return $this->photo ? asset('storage/'.$this->photo) : null;
    }

    /** Store a new profile picture, replacing the old one. */
    public function replacePhoto(?UploadedFile $file): void
    {
        if (! $file) {
            return;
        }

        $old = $this->photo;
        $this->update(['photo' => $file->store('photos', 'public')]);

        if ($old) {
            Storage::disk('public')->delete($old);
        }
    }

    public function removePhoto(): void
    {
        if ($this->photo) {
            Storage::disk('public')->delete($this->photo);
            $this->update(['photo' => null]);
        }
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->join('');
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'admin' => 'Administrator',
            'librarian' => 'Librarian',
            default => $this->member ? ucfirst($this->member->type) : 'Member',
        };
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isStaff(): bool
    {
        return $this->hasRole('admin', 'librarian');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
