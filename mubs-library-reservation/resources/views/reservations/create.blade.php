@extends('layouts.app')

@section('title', 'Reserve a book')

@section('content')
<!-- Start: Main content -->
<main class="page-main">
    <div class="container page-wrap">
        <div class="reserve-layout">

            <!-- Start: Side panel -->
            <aside class="side-panel">
                <img src="{{ asset('assets/img/logo.png') }}" alt="" class="logo logo-lg">
                <p class="kicker">New reservation</p>
                <h1>Reserve a book</h1>
                <p class="intro">Tell us who you are and which title to hold. Library staff use this list at the Nakawa desk.</p>
                <ul class="tips">
                    <li><span class="num">1.</span> Use your real registration number.</li>
                    <li><span class="num">2.</span> Pick a weekday you can collect.</li>
                    <li><span class="num">3.</span> Use the exact title as it appears on the spine.</li>
                </ul>
                <a href="{{ route('reservations.index') }}" class="text-link">
                    See reservations already made
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>
            </aside>
            <!-- End: Side panel -->

            <!-- Start: Reservation form -->
            @include('reservations.partials.form', [
                'action' => route('reservations.store'),
                'method' => 'POST',
                'submitLabel' => 'Submit reservation',
            ])
            <!-- End: Reservation form -->

        </div>
    </div>
</main>
<!-- End: Main content -->
@endsection
