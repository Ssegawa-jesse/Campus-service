{{-- Book cover image, or a colourful generated cover when no image was uploaded. --}}
@if($book->coverUrl())
    <img src="{{ $book->coverUrl() }}" alt="Cover of {{ $book->title }}" class="book-cover" loading="lazy">
@else
    @php
        $palettes = [['#4f46e5', '#1e1b4b'], ['#0d9488', '#134e4a'], ['#db2777', '#831843'], ['#f59e0b', '#9a3412'], ['#0284c7', '#0c4a6e'], ['#7c3aed', '#4c1d95'], ['#059669', '#064e3b'], ['#e11d48', '#881337']];
        [$from, $to] = $palettes[$book->id % count($palettes)];
    @endphp
    <div class="gen-cover" style="background: linear-gradient(160deg, {{ $from }}, {{ $to }})" role="img" aria-label="Cover of {{ $book->title }}">
        <div class="gc-title">{{ Str::limit($book->title, 60) }}</div>
        <div class="gc-bar"></div>
        <div class="gc-author">{{ Str::limit($book->relationLoaded('authors') ? $book->authorNames() : '', 40) }}</div>
    </div>
@endif
