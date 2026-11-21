<footer class="p360-footer mt-5">
    <div class="flag-stripe"><span></span><span></span><span></span></div>
    <div class="container-xl py-5">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <img src="{{ asset('images/mubs-logo-placeholder.svg') }}" alt="MUBS logo (placeholder)" class="mubs-logo-footer">
                    <div>
                        <div class="text-white fw-semibold">Makerere University Business School</div>
                        <div class="text-white-50 small">Faculty of Computing &amp; Informatics</div>
                    </div>
                </div>
                <p class="text-white-50 small mb-2"><em>Design and Implementation of an AI-Assisted Smart Trip Planning and Interactive 360° Preview Platform for Murchison Falls National Park.</em></p>
                <p class="text-white-50 small mb-0">IT Project (BBC III) · Supervisor: Dr. Kasule Abdullah · Study area: Murchison Falls National Park (UWA).</p>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="text-gold">Plan</h6>
                <ul class="list-unstyled small">
                    <li><a href="{{ route('itinerary.index') }}">Smart Itinerary</a></li>
                    <li><a href="{{ route('budget') }}">Travel Budget</a></li>
                    <li><a href="{{ route('navigate') }}">Smart Navigation</a></li>
                    <li><a href="{{ route('assistant') }}">AI Travel Assistant</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-3">
                <h6 class="text-gold">Explore &amp; book</h6>
                <ul class="list-unstyled small">
                    <li><a href="{{ route('park') }}">About Murchison Falls</a></li>
                    <li><a href="{{ route('updates') }}">Travel Updates</a></li>
                    <li><a href="{{ route('explore') }}">360° Virtual Exploration</a></li>
                    <li><a href="{{ route('packages.index') }}">Safari packages</a></li>
                    <li><a href="{{ route('register', ['as' => 'operator']) }}">Register as a tour operator</a></li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h6 class="text-gold">Research contact</h6>
                <ul class="list-unstyled small text-white-50">
                    <li><i class="bi bi-envelope me-2"></i><a href="mailto:{{ setting('admin_email', config('pearl360.admin_email')) }}">{{ setting('admin_email', config('pearl360.admin_email')) }}</a></li>
                    <li><i class="bi bi-telephone me-2"></i><a href="tel:+{{ ltrim(setting('admin_phone', config('pearl360.admin_phone')), '+') }}">+{{ ltrim(setting('admin_phone', config('pearl360.admin_phone')), '+') }}</a></li>
                    <li><i class="bi bi-chat-dots me-2"></i><a href="{{ route('contact') }}">Contact form</a></li>
                    <li class="mt-2"><i class="bi bi-geo-alt me-2"></i>MUBS, Nakawa, Kampala</li>
                </ul>
            </div>
        </div>
        <hr class="border-secondary">
        <div class="d-flex flex-wrap justify-content-between small text-white-50 gap-2">
            <span>&copy; {{ date('Y') }} MUBS IT Project · Fees are indicative UWA planning figures – confirm with the Uganda Wildlife Authority.</span>
            <span>Map data &copy; OpenStreetMap contributors · 360° viewer: Pannellum · Weather: Open-Meteo</span>
        </div>
    </div>
</footer>
