@php($link = fn ($route, $pattern) => request()->routeIs($pattern) ? 'nav-link active' : 'nav-link')
<div class="card sidebar"><div class="card-body p-2">
    <nav class="nav flex-column small">
        <a class="{{ $link('dashboard', 'dashboard') }}" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a class="{{ $link('', 'admin.circulation.*') }}" href="{{ route('admin.circulation.index') }}"><i class="bi bi-qr-code-scan"></i> Issue / Return</a>
        <a class="{{ $link('', 'admin.loans.*') }}" href="{{ route('admin.loans.index') }}"><i class="bi bi-arrow-left-right"></i> Loans</a>
        <a class="{{ $link('', 'admin.reservations.*') }}" href="{{ route('admin.reservations.index') }}"><i class="bi bi-bookmark-star"></i> Reservations</a>
        <div class="text-uppercase text-muted mt-3 mb-1 px-3" style="font-size:.7rem">Catalogue</div>
        <a class="{{ $link('', 'admin.books.*') }}{{ request()->routeIs('admin.copies.*') ? ' active' : '' }}" href="{{ route('admin.books.index') }}"><i class="bi bi-book"></i> Books &amp; copies</a>
        <a class="{{ $link('', 'admin.categories.*') }}" href="{{ route('admin.categories.index') }}"><i class="bi bi-tags"></i> Categories</a>
        <a class="{{ $link('', 'admin.resources.*') }}" href="{{ route('admin.resources.index') }}"><i class="bi bi-file-earmark-pdf"></i> Past papers</a>
        <a class="{{ $link('', 'admin.suggestions.*') }}" href="{{ route('admin.suggestions.index') }}"><i class="bi bi-lightbulb"></i> Recommendations</a>
        <div class="text-uppercase text-muted mt-3 mb-1 px-3" style="font-size:.7rem">People &amp; money</div>
        <a class="{{ $link('', 'admin.members.*') }}" href="{{ route('admin.members.index') }}"><i class="bi bi-people"></i> Members</a>
        <a class="{{ $link('', 'admin.fines.*') }}" href="{{ route('admin.fines.index') }}"><i class="bi bi-cash-coin"></i> Fines</a>
        <a class="{{ $link('', 'admin.reports.*') }}" href="{{ route('admin.reports.index') }}"><i class="bi bi-bar-chart"></i> Reports</a>
        @if(auth()->user()->isAdmin())
            <div class="text-uppercase text-muted mt-3 mb-1 px-3" style="font-size:.7rem">Administration</div>
            <a class="{{ $link('', 'admin.staff.*') }}" href="{{ route('admin.staff.index') }}"><i class="bi bi-person-badge"></i> Staff accounts</a>
            <a class="{{ $link('', 'admin.settings.*') }}" href="{{ route('admin.settings.edit') }}"><i class="bi bi-sliders"></i> Loan rules &amp; fines</a>
            <a class="{{ $link('', 'admin.notifications.*') }}" href="{{ route('admin.notifications.index') }}"><i class="bi bi-chat-dots"></i> SMS / email log</a>
            <a class="{{ $link('', 'admin.audit.*') }}" href="{{ route('admin.audit.index') }}"><i class="bi bi-shield-check"></i> Audit log</a>
        @endif
    </nav>
</div></div>
