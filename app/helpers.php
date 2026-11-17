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

if (! function_exists('youtube_id')) {
    function youtube_id(?string $url): ?string
    {
        return ($embed = youtube_embed($url)) ? substr($embed, -11) : null;
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

if (! function_exists('site_logo')) {
    /**
     * URL of the site logo, checked in this order:
     *  1. the logo uploaded in Admin → Settings → Logo
     *  2. a file you put yourself in public/images: logo.png, logo.jpg, logo.jpeg or logo.webp
     *  3. public/images/logo.svg – only if it really is an SVG (a renamed PNG would show as a broken image)
     *  4. the original Pearl360 logo (public/images/pearl360-default-logo.svg)
     * A ?v=… version is added so browsers show a new logo straight away instead of an old cached one.
     */
    function site_logo(): string
    {
        $candidates = [setting('site_logo')];
        foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
            $candidates[] = "images/logo.{$ext}";
        }

        foreach (array_filter($candidates) as $path) {
            if (is_file(public_path($path))) {
                return media_url($path).'?v='.filemtime(public_path($path));
            }
        }

        $svg = public_path('images/logo.svg');
        if (is_file($svg) && str_contains((string) file_get_contents($svg, false, null, 0, 512), '<svg')) {
            return asset('images/logo.svg').'?v='.filemtime($svg);
        }

        return default_logo();
    }
}

if (! function_exists('default_logo')) {
    function default_logo(): string
    {
        return asset('images/pearl360-default-logo.svg');
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
