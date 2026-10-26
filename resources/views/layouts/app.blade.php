<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Home') · {{ \App\Support\LibrarySettings::get('library_name') }}</title>
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>
        :root { --lib-primary: #0b3d91; }
        body { background: #f5f7fb; min-height: 100vh; display: flex; flex-direction: column; }
        main { flex: 1; }
        .navbar-brand { font-weight: 700; letter-spacing: .3px; }
        .bg-lib { background: var(--lib-primary) !important; }
        .btn-lib { background: var(--lib-primary); color: #fff; }
        .btn-lib:hover { background: #082d6b; color: #fff; }
        .stat-card .display-6 { font-weight: 700; }
        .book-cover { width: 100%; aspect-ratio: 2/3; object-fit: cover; background: #e3e8f2; display: flex; align-items: center; justify-content: center; color: #8a96ad; font-size: 2.5rem; border-radius: .375rem; }
        .table td, .table th { vertical-align: middle; }
        .sidebar .nav-link { color: #334; border-radius: .375rem; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { background: #e7edf8; color: var(--lib-primary); }
        @media print { .no-print { display: none !important; } body { background: #fff; } }
    </style>
    @stack('head')
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-lib no-print">
    <div class="container">
        <a class="navbar-brand" href="{{ route('home') }}"><i class="bi bi-book-half"></i> {{ \App\Support\LibrarySettings::get('library_name') }}</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link @if(request()->routeIs('catalog.*')) active @endif" href="{{ route('catalog.index') }}">Catalogue</a></li>
                <li class="nav-item"><a class="nav-link @if(request()->routeIs('resources.*')) active @endif" href="{{ route('resources.index') }}">Past papers &amp; e-resources</a></li>
                <li class="nav-item"><a class="nav-link @if(request()->routeIs('suggestions.*')) active @endif" href="{{ route('suggestions.index') }}">Recommendations</a></li>
                @auth
                    @if(auth()->user()->isStaff())
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('admin.circulation.*')) active @endif" href="{{ route('admin.circulation.index') }}"><i class="bi bi-qr-code-scan"></i> Circulation</a></li>
                    @endif
                @endauth
            </ul>
            <ul class="navbar-nav">
                @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-person-circle"></i> {{ auth()->user()->name }}</a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                            @if(auth()->user()->member)
                                <li><a class="dropdown-item" href="{{ route('my.loans') }}"><i class="bi bi-journal-bookmark"></i> My loans</a></li>
                                <li><a class="dropdown-item" href="{{ route('my.reservations') }}"><i class="bi bi-bookmark-star"></i> My reservations</a></li>
                                <li><a class="dropdown-item" href="{{ route('my.fines') }}"><i class="bi bi-cash-coin"></i> Fines &amp; payments</a></li>
                                <li><a class="dropdown-item" href="{{ route('my.card') }}"><i class="bi bi-person-vcard"></i> Library card</a></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-gear"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">@csrf
                                    <button class="dropdown-item"><i class="bi bi-box-arrow-right"></i> Log out</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Log in</a></li>
                    <li class="nav-item"><a class="btn btn-light btn-sm ms-lg-2 mt-1" href="{{ route('register') }}">Register</a></li>
                @endauth
            </ul>
        </div>
    </div>
</nav>

<main class="py-4">
    @hasSection('sidebar')
        <div class="container-fluid px-lg-4">
            <div class="row">
                <aside class="col-lg-2 mb-3 no-print">@yield('sidebar')</aside>
                <div class="col-lg-10">
                    @include('partials.flash')
                    @yield('content')
                </div>
            </div>
        </div>
    @else
        <div class="container">
            @include('partials.flash')
            @yield('content')
        </div>
    @endif
</main>

<footer class="py-3 bg-white border-top small text-muted no-print">
    <div class="container d-flex flex-wrap justify-content-between">
        <span>&copy; {{ date('Y') }} {{ \App\Support\LibrarySettings::get('library_name') }} · Makerere University Business School</span>
        <span>SmartLib Library Management System</span>
    </div>
</footer>

<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
@stack('scripts')
</body>
</html>
