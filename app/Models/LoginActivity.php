<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginActivity extends Model
{
    public const UPDATED_AT = null;

    public const EVENTS = [
        'registered' => ['New registration', 'success'],
        'login' => ['Logged in', 'primary'],
        'failed' => ['Failed login', 'danger'],
        'blocked' => ['Suspended account tried to log in', 'warning'],
        'logout' => ['Logged out', 'secondary'],
        'password_reset' => ['Password reset', 'info'],
    ];

    protected $fillable = ['user_id', 'email', 'event', 'ip', 'user_agent'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $event, ?User $user = null, ?string $email = null): void
    {
        static::create([
            'user_id' => $user?->id,
            'email' => $email ?? $user?->email,
            'event' => $event,
            'ip' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 255),
        ]);

        if ($user && in_array($event, ['login', 'registered'], true)) {
            $user->forceFill(['last_login_at' => now(), 'last_login_ip' => request()->ip()])->saveQuietly();
        }
    }

    public function label(): string
    {
        return self::EVENTS[$this->event][0] ?? ucfirst($this->event);
    }

    public function color(): string
    {
        return self::EVENTS[$this->event][1] ?? 'secondary';
    }
}
