<span class="text-gold" title="{{ $rating }} / 5">@for ($i = 1; $i <= 5; $i++)<i class="bi bi-star{{ $rating >= $i ? '-fill' : ($rating >= $i - 0.5 ? '-half' : '') }}"></i>@endfor</span>
