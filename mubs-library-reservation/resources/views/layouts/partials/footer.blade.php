<!-- Start: Footer -->
@if ($compact)
    <footer class="site-footer footer-compact">
        <div class="footer-accent"></div>
        <div class="container footer-inner">
            <div class="footer-row">
                <p class="footer-muted">© {{ date('Y') }} <strong>MUBS Library</strong>. All rights reserved.</p>
                <p class="footer-faint">Nakawa Campus · Mon–Fri, 8:00am – 8:00pm</p>
            </div>
        </div>
    </footer>
@else
    <footer class="site-footer">
        <div class="footer-accent"></div>
        <div class="container footer-inner">
            <div class="footer-row">
                <div class="footer-brand">
                    <img src="{{ asset('assets/img/logo.png') }}" alt="Makerere University Business School" class="logo logo-md">
                    <div>
                        <p>MUBS Library</p>
                        <p class="footer-muted">Makerere University Business School · Nakawa</p>
                    </div>
                </div>
                <p class="footer-muted">Mon–Fri, 8:00am – 8:00pm · library@mubs.ac.ug</p>
            </div>
            <div class="footer-meta">
                <p>© {{ date('Y') }} MUBS Library. All rights reserved.</p>
            </div>
        </div>
    </footer>
@endif
<!-- End: Footer -->
