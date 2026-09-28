<nav class="navbar navbar-expand-lg p360-nav sticky-top">
    <div class="container-xl">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}">
            <img src="{{ asset('images/logo.svg') }}" alt="" width="36" height="36">
            <span class="brand-text">Pearl<span>360</span></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('parks.*', 'tour.*') ? 'active' : '' }}" href="{{ route('parks.index') }}">Parks</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('map') ? 'active' : '' }}" href="{{ route('map') }}">Map</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('planner.*') ? 'active' : '' }}" href="{{ route('planner.index') }}">Trip Planner</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('parks.compare') ? 'active' : '' }}" href="{{ route('parks.compare') }}">Compare</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('packages.*', 'operators.*') ? 'active' : '' }}" href="{{ route('packages.index') }}">Safari Packages</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}">About</a></li>
            </ul>
            <div class="d-flex align-items-center gap-2">
                @auth
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-forest dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                            <img src="{{ media_url(auth()->user()->avatar, 'images/avatar.svg') }}" class="rounded-circle" width="26" height="26" alt="">
                            {{ Str::words(auth()->user()->name, 1, '') }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><span class="dropdown-item-text small text-muted text-capitalize">{{ auth()->user()->role }} account</span></li>
                            <li><a class="dropdown-item" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>My profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">@csrf
                                    <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Log out</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn btn-sm btn-link text-forest fw-semibold">Log in</a>
                    <a href="{{ route('register') }}" class="btn btn-sm btn-gold">Sign up</a>
                @endauth
            </div>
        </div>
    </div>
</nav>
