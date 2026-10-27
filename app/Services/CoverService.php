<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Downloads real book covers from Open Library using the ISBN (needs internet).
 */
class CoverService
{
    public function fetch(Book $book): bool
    {
        $isbn = preg_replace('/[^0-9Xx]/', '', (string) $book->isbn);
        if ($isbn === '') {
            return false;
        }

        try {
            // "default=false" makes Open Library return 404 instead of a blank image when it has no cover.
            $response = Http::timeout(15)->get("https://covers.openlibrary.org/b/isbn/{$isbn}-L.jpg", ['default' => 'false']);
        } catch (Throwable) {
            return false;
        }

        if (! $response->successful() || ! str_starts_with((string) $response->header('Content-Type'), 'image/') || strlen($response->body()) < 1000) {
            return false;
        }

        $path = "covers/{$book->id}-{$isbn}.jpg";
        Storage::disk('public')->put($path, $response->body());

        if ($book->cover_image && $book->cover_image !== $path) {
            Storage::disk('public')->delete($book->cover_image);
        }
        $book->update(['cover_image' => $path]);

        return true;
    }
}
