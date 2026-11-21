<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Live operational alert: Paraa ferry schedule, road/gate status, weather advisories, UWA notices. */
class TravelUpdate extends Model
{
    protected $fillable = ['category', 'title', 'body', 'severity', 'location', 'is_active', 'is_pinned', 'valid_from', 'valid_until', 'created_by'];

    public const CATEGORIES = [
        'ferry' => ['Paraa ferry', 'water'],
        'road' => ['Roads', 'sign-turn-right'],
        'gate' => ['Gates', 'door-open'],
        'weather' => ['Weather advisory', 'cloud-rain'],
        'conservation' => ['UWA conservation notice', 'tree'],
        'general' => ['General', 'info-circle'],
    ];

    public const SEVERITIES = ['info' => 'info', 'warning' => 'warning', 'critical' => 'danger'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_pinned' => 'boolean', 'valid_from' => 'date', 'valid_until' => 'date'];
    }

    /** Active and inside its validity window today. */
    public function scopeCurrent(Builder $q): Builder
    {
        $today = now()->toDateString();

        return $q->where('is_active', true)
            ->where(fn ($w) => $w->whereNull('valid_from')->orWhereDate('valid_from', '<=', $today))
            ->where(fn ($w) => $w->whereNull('valid_until')->orWhereDate('valid_until', '>=', $today))
            ->orderByDesc('is_pinned')->orderByRaw("CASE severity WHEN 'critical' THEN 0 WHEN 'warning' THEN 1 ELSE 2 END")->latest();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category][0] ?? ucfirst($this->category);
    }

    public function icon(): string
    {
        return self::CATEGORIES[$this->category][1] ?? 'info-circle';
    }

    public function color(): string
    {
        return self::SEVERITIES[$this->severity] ?? 'info';
    }
}
