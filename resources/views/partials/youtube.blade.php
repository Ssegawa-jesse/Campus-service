{{-- Lightweight YouTube player: shows the thumbnail, loads the real player on click.
     Needs $url (any YouTube link) and optional $title. --}}
@php $ytId = youtube_id($url); @endphp
@if ($ytId)
    <div class="ratio ratio-16x9 ratio-video yt-lite" data-yt="{{ $ytId }}" data-title="{{ $title ?? 'Video' }}" role="button" tabindex="0" aria-label="Play video: {{ $title ?? 'Video' }}"
         style="background-image:url('https://i.ytimg.com/vi/{{ $ytId }}/hqdefault.jpg')">
        <span class="yt-play"><i class="bi bi-play-fill"></i></span>
    </div>
    <div class="small mt-1 d-flex justify-content-between gap-2">
        <span class="text-muted">{{ $title ?? '' }}</span>
        <a href="https://www.youtube.com/watch?v={{ $ytId }}" target="_blank" rel="noopener" class="text-nowrap"><i class="bi bi-youtube text-danger"></i> Watch on YouTube</a>
    </div>
@endif
