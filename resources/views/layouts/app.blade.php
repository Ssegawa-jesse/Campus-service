<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Explore Uganda in 360°') · {{ config('pearl360.name') }}</title>
    <meta name="description" content="@yield('description', setting('site_tagline', 'Virtual 360° tours and smart trip planning for Uganda\'s national parks.'))">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    @stack('styles')
    <link href="{{ asset('css/pearl360.css') }}?v=1" rel="stylesheet">
</head>
<body class="@yield('body_class')">
    @include('partials.nav')

    <main>
        @hasSection('dashboard')
            <div class="container-xl py-4">
                <div class="row g-4">
                    <aside class="col-lg-3">@include('partials.sidebar')</aside>
                    <section class="col-lg-9">
                        @include('partials.flash')
                        @yield('content')
                    </section>
                </div>
            </div>
        @else
            @unless (View::hasSection('no_flash'))
                <div class="container-xl">@include('partials.flash')</div>
            @endunless
            @yield('content')
        @endif
    </main>

    @unless (View::hasSection('no_footer'))
        @include('partials.footer')
    @endunless

    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/pearl360.js') }}?v=1"></script>
    @stack('scripts')
</body>
</html>
