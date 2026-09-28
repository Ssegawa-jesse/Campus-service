<footer class="p360-footer mt-5">
    <div class="flag-stripe"><span></span><span></span><span></span></div>
    <div class="container-xl py-5">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <img src="{{ asset('images/logo.svg') }}" width="40" height="40" alt="">
                    <span class="brand-text text-white fs-4">Pearl<span>360</span></span>
                </div>
                <p class="text-white-50 small">{{ setting('site_tagline', 'See Uganda\'s national parks in 360° before you go.') }}</p>
                <p class="text-white-50 small mb-0">A MUBS Business Computing research project (Group 12) · Case study: Uganda Wildlife Authority.</p>
            </div>
            <div class="col-6 col-md-2">
                <h6 class="text-gold">Explore</h6>
                <ul class="list-unstyled small">
                    <li><a href="{{ route('parks.index') }}">All parks</a></li>
                    <li><a href="{{ route('map') }}">Interactive map</a></li>
                    <li><a href="{{ route('planner.index') }}">Trip planner</a></li>
                    <li><a href="{{ route('parks.compare') }}">Compare parks</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-3">
                <h6 class="text-gold">For partners</h6>
                <ul class="list-unstyled small">
                    <li><a href="{{ route('register', ['as' => 'operator']) }}">Register as a tour operator</a></li>
                    <li><a href="{{ route('packages.index') }}">Safari packages</a></li>
                    <li><a href="{{ route('contact') }}">Contact us</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h6 class="text-gold">Contact</h6>
                <ul class="list-unstyled small text-white-50">
                    <li><i class="bi bi-envelope me-2"></i><a href="mailto:{{ setting('admin_email', config('pearl360.admin_email')) }}">{{ setting('admin_email', config('pearl360.admin_email')) }}</a></li>
                    <li><i class="bi bi-telephone me-2"></i><a href="tel:+{{ ltrim(setting('admin_phone', config('pearl360.admin_phone')), '+') }}">+{{ ltrim(setting('admin_phone', config('pearl360.admin_phone')), '+') }}</a></li>
                    <li><i class="bi bi-whatsapp me-2"></i><a href="https://wa.me/{{ ltrim(setting('support_whatsapp', config('pearl360.admin_phone')), '+') }}" target="_blank" rel="noopener">WhatsApp us</a></li>
                    <li class="mt-2"><i class="bi bi-geo-alt me-2"></i>Kampala, Uganda</li>
                </ul>
            </div>
        </div>
        <hr class="border-secondary">
        <div class="d-flex flex-wrap justify-content-between small text-white-50">
            <span>&copy; {{ date('Y') }} Pearl360 Uganda. Park fees shown are indicative – confirm with UWA.</span>
            <span>Map data &copy; OpenStreetMap contributors · 360° viewer: Pannellum</span>
        </div>
    </div>
</footer>
