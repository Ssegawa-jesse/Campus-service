<nav class="navbar navbar-expand-xl p360-nav sticky-top">
    <div class="container-xl">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}">
            <img src="{{ site_logo() }}" alt="MUBS logo" class="site-logo" onerror="this.onerror=null;this.src='{{ default_logo() }}'">
            @if (setting('logo_show_name', '1') !== '0')
                <span class="brand-text lh-1">Murchison<span> Falls</span><small class="d-block text-muted fw-normal" style="font-size:.7rem;letter-spacing:.02em">Smart Tourism Platform</small></span>
            @endif
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('plan.*') ? 'active' : '' }}" href="{{ route('plan.index') }}">Smart itinerary</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('budget') ? 'active' : '' }}" href="{{ route('budget') }}">Budget</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('map') ? 'active' : '' }}" href="{{ route('map') }}">Map &amp; routes</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('explore', 'tour.*') ? 'active' : '' }}" href="{{ route('explore') }}">360° explore</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('packages.*', 'operators.*') ? 'active' : '' }}" href="{{ route('packages.index') }}">Tour operators</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('assistant') ? 'active' : '' }}" href="{{ route('assistant') }}">AI assistant</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('updates') ? 'active' : '' }}" href="{{ route('updates') }}">Updates</a></li>
            </ul>
            <div class="d-flex align-items-center gap-2">
                @auth
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.operators.index', ['status' => 'pending']) }}" class="btn btn-sm btn-link text-forest position-relative me-1" title="{{ $pendingOperatorCount }} operator(s) awaiting approval">
                            <i class="bi bi-bell fs-5"></i>
                            @if ($pendingOperatorCount)<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $pendingOperatorCount }}</span>@endif
                        </a>
                    @endif
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
