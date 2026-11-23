<!-- Start: Header -->
<header class="site-header">
    <div class="header-bar">
        <div class="container">
            <div class="header-row">
                <a href="{{ route('home') }}" class="brand">
                    <img src="{{ asset('assets/img/logo.png') }}" alt="Makerere University Business School" class="logo logo-sm">
                    <span class="brand-text">
                        <span class="brand-name">MUBS Library</span>
                        <span class="brand-campus">Nakawa Campus</span>
                    </span>
                </a>
                <nav class="desktop-nav">
                    <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'is-active' : '' }}">Home</a>
                    <a href="{{ route('reservations.index') }}" class="nav-link {{ request()->routeIs('reservations.index') ? 'is-active' : '' }}">Reservations</a>
                    <a href="{{ route('reservations.create') }}" class="btn btn-primary btn-sm glow-orange">Reserve a book</a>
                </nav>
                <label for="mobile-nav-toggle" class="menu-btn" aria-label="Open menu">
                    <svg class="icon icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                </label>
            </div>
            <input type="checkbox" id="mobile-nav-toggle" class="nav-toggle">
            <nav class="mobile-nav">
                <div class="mobile-nav-links">
                    <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'is-active' : '' }}">Home</a>
                    <a href="{{ route('reservations.index') }}" class="nav-link {{ request()->routeIs('reservations.index') ? 'is-active' : '' }}">Reservations</a>
                    <a href="{{ route('reservations.create') }}" class="btn btn-primary btn-sm">Reserve a book</a>
                </div>
            </nav>
        </div>
    </div>
</header>
<!-- End: Header -->
