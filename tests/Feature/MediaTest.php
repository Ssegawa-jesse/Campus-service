<?php

namespace Tests\Feature;

use App\Models\Book;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Covers and photos are served by the app itself, so no public/storage symlink is needed. */
class MediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_covers_are_served_without_the_storage_symlink(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
        $book = Book::whereNotNull('cover_image')->first();

        $this->assertStringStartsWith('/media/covers/', $book->coverUrl());
        $this->get('/')->assertOk()->assertSee($book->coverUrl(), false);

        $response = $this->get($book->coverUrl());
        $response->assertOk();
        $this->assertSame('image/jpeg', $response->headers->get('Content-Type'));
    }

    public function test_only_cover_and_photo_files_can_be_fetched(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('covers/a.jpg', 'x');
        Storage::disk('local')->put('resources/secret.pdf', 'x');

        $this->get('/media/covers/a.jpg')->assertOk();
        $this->get('/media/covers/missing.jpg')->assertNotFound();
        $this->get('/media/resources/secret.pdf')->assertNotFound();
        $this->get('/media/covers/..%2F..%2F.env')->assertNotFound();
        $this->get('/media/covers/../../.env')->assertNotFound();
    }
}
