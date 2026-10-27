<div class="col-lg-5 auth-side d-none d-lg-flex flex-column">
    <div class="d-flex align-items-center gap-2 mb-4 fw-bold fs-5"><span class="brand-mark" style="width:40px;height:40px;border-radius:10px;background:rgba(255,255,255,.2);display:grid;place-items:center"><i class="bi bi-book-half"></i></span> {{ \App\Support\LibrarySettings::get('library_name') }}</div>
    <h2 class="fw-bold mb-4">{{ $heading }}</h2>
    <div class="feature"><i class="bi bi-search"></i><div><div class="fw-semibold">Search from anywhere</div><div class="small opacity-75">See what's on the shelf before you walk to the library.</div></div></div>
    <div class="feature"><i class="bi bi-bookmark-star"></i><div><div class="fw-semibold">Reserve &amp; renew online</div><div class="small opacity-75">Get an SMS when your reserved book is ready.</div></div></div>
    <div class="feature"><i class="bi bi-phone"></i><div><div class="fw-semibold">Pay fines with Mobile Money</div><div class="small opacity-75">MTN MoMo, Airtel Money or card — no queues.</div></div></div>
    <div class="feature"><i class="bi bi-lightbulb"></i><div><div class="fw-semibold">Recommend books</div><div class="small opacity-75">Tell the library which books to buy next.</div></div></div>
</div>
