<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

/**
 * Serves book covers and profile pictures from storage/app/public.
 * This avoids relying on the public/storage symlink, which Windows often
 * cannot create without administrator rights.
 */
class MediaController extends Controller
{
    public function __invoke(string $folder, string $file)
    {
        $path = "{$folder}/{$file}";
        $disk = Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'public, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
