<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Services\CoverService;
use Illuminate\Console\Command;

class FetchBookCovers extends Command
{
    protected $signature = 'library:fetch-covers {--all : Replace covers that already exist}';

    protected $description = 'Download real book covers from Open Library by ISBN (needs internet)';

    public function handle(CoverService $covers): int
    {
        $books = Book::whereNotNull('isbn')
            ->when(! $this->option('all'), fn ($q) => $q->whereNull('cover_image'))
            ->get();

        $found = 0;
        foreach ($books as $book) {
            $ok = $covers->fetch($book);
            $found += (int) $ok;
            $this->line(($ok ? '<info>found</info>    ' : '<comment>not found</comment> ').$book->title);
        }

        $this->info("Downloaded {$found} of {$books->count()} covers.");

        return self::SUCCESS;
    }
}
