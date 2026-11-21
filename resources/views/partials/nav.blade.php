<div class="mubs-strip small">
    <div class="container-xl d-flex align-items-center justify-content-between gap-2 py-1">
        <span class="d-flex align-items-center gap-2">
            <img src="{{ asset('images/mubs-logo-placeholder.svg') }}" alt="Makerere University Business School logo (placeholder)" class="mubs-logo">
            <span><strong>Makerere University Business School</strong> <span class="d-none d-md-inline">· Faculty of Computing &amp; Informatics · IT Project Research</span></span>
        </span>
        <a href="{{ route('updates') }}" class="text-white text-decoration-none d-none d-sm-inline"><i class="bi bi-broadcast"></i> Travel updates</a>
    </div>
</div>
<nav class="navbar navbar-expand-xl p360-nav sticky-top">
    <div class="container-xl">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}">
            <img src="{{ site_logo() }}" alt="{{ config('pearl360.name') }}" class="site-logo" onerror="this.onerror=null;this.src='{{ default_logo() }}'">
            @if (setting('logo_show_name', '1') !== '0')<span class="brand-text">Murchison<span>Smart</span></span>@endif
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav mx-auto">
                @foreach ([
                    ['itinerary.index', 'itinerary.*', 'calendar2-week', 'Smart Itinerary'],
                    ['budget', 'budget', 'calculator', 'Budget'],
                    ['navigate', 'navigate*', 'map', 'Navigation'],
                    ['assistant', 'assistant', 'robot', 'AI Assistant'],
                    ['updates', 'updates', 'broadcast', 'Updates'],
                    ['explore', 'explore', 'badge-vr', '360° Explore'],
                    ['packages.index', 'packages.*', 'bag', 'Packages'],
                ] as [$r, $active, $icon, $label])
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs($active) ? 'active' : '' }}" href="{{ route($r) }}"><i class="bi bi-{{ $icon }} me-1"></i>{{ $label }}</a></li>
                @endforeach
            </ul>
            <div class="d-flex align-items-center gap-2">
                @if (count(session('itinerary_must_do', [])))
                    <a href="{{ route('itinerary.index') }}" class="btn btn-sm btn-link text-forest position-relative" title="Activities added to your itinerary">
                        <i class="bi bi-calendar-plus fs-5"></i><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-gold text-dark">{{ count(session('itinerary_must_do', [])) }}</span>
                    </a>
                @endif
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
                            <li><span class="dropdown-item-text small text-muted">{{ ['admin' => 'Admin & MUBS Researcher', 'operator' => 'Tour Operator', 'tourist' => 'Tourist'][auth()->user()->role] ?? auth()->user()->role }} portal</span></li>
                            <li><a class="dropdown-item" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>My portal</a></li>
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>My profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">@csrf
                                    <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Log out / switch role</button>
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
