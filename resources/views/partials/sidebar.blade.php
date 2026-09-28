@php
    $user = auth()->user();
    $menus = [
        'admin' => [
            ['admin.dashboard', 'speedometer2', 'Dashboard & analytics'],
            ['admin.parks.index', 'tree', 'Parks, media & 360°'],
            ['admin.operators.index', 'briefcase', 'Tour operators'],
            ['admin.users.index', 'people', 'Users'],
            ['admin.bookings.index', 'calendar-check', 'Bookings'],
            ['admin.payments.index', 'cash-coin', 'Payments'],
            ['admin.reviews.index', 'star', 'Reviews'],
            ['admin.sms.index', 'chat-dots', 'SMS centre'],
            ['admin.messages.index', 'envelope', 'Contact messages'],
            ['admin.settings.edit', 'gear', 'Settings'],
        ],
        'operator' => [
            ['operator.dashboard', 'speedometer2', 'Dashboard'],
            ['operator.bookings.index', 'inbox', 'Booking enquiries'],
            ['operator.packages.index', 'backpack', 'My packages'],
            ['operator.profile.edit', 'building', 'Company profile'],
        ],
        'tourist' => [
            ['tourist.dashboard', 'speedometer2', 'My dashboard'],
            ['tourist.bookings.index', 'calendar-check', 'My bookings'],
            ['planner.index', 'magic', 'Plan a new trip'],
            ['parks.index', 'binoculars', 'Explore parks'],
        ],
    ][$user->role] ?? [];
@endphp
<div class="card p360-sidebar border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-3">
            <img src="{{ media_url($user->avatar, 'images/avatar.svg') }}" class="rounded-circle" width="48" height="48" alt="">
            <div>
                <div class="fw-semibold">{{ $user->name }}</div>
                <span class="badge role-badge role-{{ $user->role }} text-capitalize">{{ $user->role === 'admin' ? 'Administrator (UWA)' : ($user->role === 'operator' ? 'Tour operator' : 'Tourist') }}</span>
            </div>
        </div>
        <nav class="nav flex-column">
            @foreach ($menus as [$route, $icon, $label])
                <a href="{{ route($route) }}" class="nav-link {{ request()->routeIs(Str::beforeLast($route, '.').'*') && $route !== 'planner.index' && $route !== 'parks.index' ? 'active' : '' }}">
                    <i class="bi bi-{{ $icon }} me-2"></i>{{ $label }}
                </a>
            @endforeach
            <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}"><i class="bi bi-person-gear me-2"></i>Account settings</a>
        </nav>
    </div>
</div>
