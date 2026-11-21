@php
    $user = auth()->user();
    $menus = [
        'admin' => [
            ['admin.dashboard', 'speedometer2', 'Dashboard & analytics'],
            ['admin.studio', 'badge-vr', 'HP5 360° Media & Hotspot Studio'],
            ['admin.uwa-activities.index', 'cash-stack', 'Activities & UWA prices'],
            ['admin.places.index', 'geo-alt', 'Map places & gates'],
            ['admin.travel-updates.index', 'broadcast', 'Travel updates'],
            ['admin.itineraries.index', 'calendar2-week', 'Smart itineraries'],
            ['admin.bookings.index', 'calendar-check', 'Bookings'],
            ['admin.payments.index', 'cash-coin', 'Payments'],
            ['admin.notifications.index', 'send-check', 'SMS & email logs'],
            ['admin.assistant-logs', 'robot', 'AI assistant logs'],
            ['admin.parks.index', 'tree', 'Park profile & gallery'],
            ['admin.operators.index', 'briefcase', 'Tour operators'],
            ['admin.users.index', 'people', 'Users & admins'],
            ['admin.users.activity', 'clock-history', 'Login activity'],
            ['admin.reviews.index', 'star', 'Reviews'],
            ['admin.sms.index', 'chat-dots', 'SMS centre'],
            ['admin.messages.index', 'envelope', 'Contact messages'],
            ['admin.settings.edit', 'gear', 'Settings'],
        ],
        'operator' => [
            ['operator.dashboard', 'speedometer2', 'Dashboard'],
            ['operator.bookings.index', 'inbox', 'Tourist bookings'],
            ['operator.packages.index', 'backpack', 'Safari packages & media'],
            ['operator.profile.edit', 'building', 'Company profile'],
        ],
        'tourist' => [
            ['tourist.dashboard', 'speedometer2', 'My dashboard'],
            ['itinerary.index', 'calendar2-week', 'Smart Itinerary'],
            ['tourist.bookings.index', 'calendar-check', 'My bookings & payments'],
            ['budget', 'calculator', 'Budget calculator'],
            ['assistant', 'robot', 'AI Travel Assistant'],
            ['explore', 'badge-vr', '360° previews'],
        ],
    ][$user->role] ?? [];
@endphp
<div class="card p360-sidebar border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-3">
            <img src="{{ media_url($user->avatar, 'images/avatar.svg') }}" class="rounded-circle" width="48" height="48" alt="">
            <div>
                <div class="fw-semibold">{{ $user->name }}</div>
                <span class="badge role-badge role-{{ $user->role }} text-capitalize">{{ $user->role === 'admin' ? 'Admin & MUBS Researcher' : ($user->role === 'operator' ? 'Tour operator' : 'Tourist') }}</span>
            </div>
        </div>
        <nav class="nav flex-column">
            @foreach ($menus as [$route, $icon, $label])
                <a href="{{ route($route) }}" class="nav-link {{ (str_starts_with($route, 'admin.users.') ? request()->routeIs($route === 'admin.users.activity' ? $route : ['admin.users.index', 'admin.users.create']) : request()->routeIs(substr_count($route, '.') >= 2 ? Str::beforeLast($route, '.').'*' : $route)) ? 'active' : '' }}">
                    <i class="bi bi-{{ $icon }} me-2"></i>{{ $label }}
                    @if ($route === 'admin.operators.index' && ($pendingOperatorCount ?? 0))<span class="badge bg-danger float-end">{{ $pendingOperatorCount }}</span>@endif
                </a>
            @endforeach
            <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}"><i class="bi bi-person-gear me-2"></i>Account settings</a>
        </nav>
    </div>
</div>
