<?php

use App\Models\Setting;

if (! function_exists('media_url')) {
    /**
     * Resolve a stored media path to a public URL.
     * Accepts full URLs (http…), paths under public/ (e.g. "uploads/parks/x.jpg") or null.
     */
    function media_url(?string $path, ?string $fallback = null): string
    {
        if (! $path) {
            return $fallback ? asset($fallback) : '';
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }
}

if (! function_exists('youtube_embed')) {
    function youtube_embed(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/))([A-Za-z0-9_-]{11})~', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }

        return null;
    }
}

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        try {
            return Setting::get($key, $default);
        } catch (\Throwable) {
            return $default; // database not migrated yet
        }
    }
}

if (! function_exists('ugx')) {
    function ugx(int|float|null $amount): string
    {
        return 'UGX '.number_format((float) $amount);
    }
}

if (! function_exists('usd_to_ugx')) {
    function usd_to_ugx(int|float|null $usd): int
    {
        return (int) round((float) $usd * (float) setting('usd_rate', config('pearl360.usd_rate')));
    }
}

if (! function_exists('month_names')) {
    function month_names(): array
    {
        return [1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    }
}
