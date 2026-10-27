<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4f46e5">
    <title>@yield('title', 'Home') · {{ \App\Support\LibrarySettings::get('library_name') }}</title>
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/smartlib.css') }}" rel="stylesheet">
    @stack('head')
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark sl-navbar no-print">
    <div class="container-xl">
        <a class="navbar-brand" href="{{ route('home') }}"><span class="brand-mark"><i class="bi bi-book-half"></i></span> {{ \App\Support\LibrarySettings::get('library_name') }}</a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto gap-lg-1">
                <li class="nav-item"><a class="nav-link @if(request()->routeIs('catalog.*')) active @endif" href="{{ route('catalog.index') }}"><i class="bi bi-search"></i> Catalogue</a></li>
                <li class="nav-item"><a class="nav-link @if(request()->routeIs('resources.*')) active @endif" href="{{ route('resources.index') }}"><i class="bi bi-file-earmark-text"></i> Past papers</a></li>
                <li class="nav-item"><a class="nav-link @if(request()->routeIs('suggestions.*')) active @endif" href="{{ route('suggestions.index') }}"><i class="bi bi-lightbulb"></i> Recommendations</a></li>
                @auth
                    @if(auth()->user()->isStaff())
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('admin.circulation.*')) active @endif" href="{{ route('admin.circulation.index') }}"><i class="bi bi-qr-code-scan"></i> Circulation</a></li>
                    @endif
                @endauth
            </ul>
            <ul class="navbar-nav align-items-lg-center">
                @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
                            @include('partials.avatar', ['user' => auth()->user(), 'size' => 30]) {{ auth()->user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li><a class="dropdown-item" href="{{ route('dashboard') }}"><i class="bi bi-grid-1x2 me-2"></i>Dashboard</a></li>
                            @if(auth()->user()->member)
                                <li><a class="dropdown-item" href="{{ route('my.loans') }}"><i class="bi bi-journal-bookmark me-2"></i>My loans</a></li>
                                <li><a class="dropdown-item" href="{{ route('my.reservations') }}"><i class="bi bi-bookmark-star me-2"></i>My reservations</a></li>
                                <li><a class="dropdown-item" href="{{ route('my.fines') }}"><i class="bi bi-cash-coin me-2"></i>Fines &amp; payments</a></li>
                                <li><a class="dropdown-item" href="{{ route('my.card') }}"><i class="bi bi-person-vcard me-2"></i>Library card</a></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person-gear me-2"></i>Profile &amp; photo</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">@csrf
                                    <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Log out</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Log in</a></li>
                    <li class="nav-item ms-lg-2"><a class="btn btn-light btn-sm px-3 fw-semibold text-primary" href="{{ route('register') }}">Register</a></li>
                @endauth
            </ul>
        </div>
    </div>
</nav>

<main class="py-4">
    @hasSection('sidebar')
        <div class="container-fluid px-lg-4">
            <div class="row g-4">
                <aside class="col-lg-3 col-xl-2 no-print">@yield('sidebar')</aside>
                <div class="col-lg-9 col-xl-10">
                    @include('partials.flash')
                    @yield('content')
                </div>
            </div>
        </div>
    @else
        <div class="container-xl">
            @include('partials.flash')
            @yield('content')
        </div>
    @endif
</main>

<footer class="sl-footer py-4 no-print">
    <div class="container-xl d-flex flex-wrap justify-content-between gap-2 small">
        <span><i class="bi bi-book-half"></i> &copy; {{ date('Y') }} {{ \App\Support\LibrarySettings::get('library_name') }} · Makerere University Business School</span>
        <span>SmartLib Library Management System</span>
    </div>
</footer>

<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
@stack('scripts')
</body>
</html>
