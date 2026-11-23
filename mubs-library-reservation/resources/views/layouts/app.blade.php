{{-- Main layout: every page extends this file and fills the "content" section. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MUBS Library') — MUBS Library</title>
    <meta name="description" content="Reserve a library book online instead of queuing at the MUBS Library front desk.">
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>
<body class="@yield('body_class', 'page')">

    {{-- Header --}}
    @include('layouts.partials.header')

    {{-- Content section --}}
    @yield('content')

    {{-- Footer --}}
    @include('layouts.partials.footer', ['compact' => ! request()->routeIs('home')])

</body>
</html>
