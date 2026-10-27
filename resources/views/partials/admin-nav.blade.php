@php($active = fn (string ...$patterns) => request()->routeIs(...$patterns) ? 'nav-link active' : 'nav-link')
<nav class="sl-sidebar">
    <div class="me">
        @include('partials.avatar', ['user' => auth()->user(), 'size' => 38])
        <div class="small lh-sm"><div class="fw-semibold">{{ auth()->user()->name }}</div><div style="color:#a5b4fc">{{ auth()->user()->roleLabel() }}</div></div>
    </div>
    <a class="{{ $active('dashboard') }}" href="{{ route('dashboard') }}"><i class="bi bi-grid-1x2"></i> Dashboard</a>
    <a class="{{ $active('admin.circulation.*') }}" href="{{ route('admin.circulation.index') }}"><i class="bi bi-qr-code-scan"></i> Issue / Return</a>
    <a class="{{ $active('admin.loans.*') }}" href="{{ route('admin.loans.index') }}"><i class="bi bi-arrow-left-right"></i> Loans</a>
    <a class="{{ $active('admin.reservations.*') }}" href="{{ route('admin.reservations.index') }}"><i class="bi bi-bookmark-star"></i> Reservations</a>
    <div class="section">Catalogue</div>
    <a class="{{ $active('admin.books.*', 'admin.copies.*') }}" href="{{ route('admin.books.index') }}"><i class="bi bi-book"></i> Books &amp; copies</a>
    <a class="{{ $active('admin.categories.*') }}" href="{{ route('admin.categories.index') }}"><i class="bi bi-tags"></i> Categories</a>
    <a class="{{ $active('admin.resources.*') }}" href="{{ route('admin.resources.index') }}"><i class="bi bi-file-earmark-pdf"></i> Past papers</a>
    <a class="{{ $active('admin.suggestions.*') }}" href="{{ route('admin.suggestions.index') }}"><i class="bi bi-lightbulb"></i> Recommendations</a>
    <div class="section">People &amp; money</div>
    <a class="{{ $active('admin.members.*') }}" href="{{ route('admin.members.index') }}"><i class="bi bi-people"></i> Members</a>
    <a class="{{ $active('admin.fines.*') }}" href="{{ route('admin.fines.index') }}"><i class="bi bi-cash-coin"></i> Fines</a>
    <a class="{{ $active('admin.reports.*') }}" href="{{ route('admin.reports.index') }}"><i class="bi bi-bar-chart-line"></i> Reports</a>
    @if(auth()->user()->isAdmin())
        <div class="section">Administration</div>
        <a class="{{ $active('admin.staff.*') }}" href="{{ route('admin.staff.index') }}"><i class="bi bi-person-badge"></i> Staff accounts</a>
        <a class="{{ $active('admin.settings.*') }}" href="{{ route('admin.settings.edit') }}"><i class="bi bi-sliders"></i> Loan rules &amp; fines</a>
        <a class="{{ $active('admin.notifications.*') }}" href="{{ route('admin.notifications.index') }}"><i class="bi bi-chat-dots"></i> SMS / email log</a>
        <a class="{{ $active('admin.audit.*') }}" href="{{ route('admin.audit.index') }}"><i class="bi bi-shield-check"></i> Audit log</a>
    @endif
</nav>
