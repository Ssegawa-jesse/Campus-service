@extends('layouts.app')

@section('title', 'Reserve a book online')
@section('body_class', '')

@section('content')
<!-- Start: Hero -->
<section class="hero bg-warm-mesh">
    <div class="container hero-inner">
        <p class="eyebrow">
            <span class="eyebrow-line"></span>
            Makerere University Business School
        </p>
        <h1 class="hero-title">
            Skip the front desk queue.<br>
            <span class="accent">Hold your book online.</span>
        </h1>
        <p class="lede">
            MUBS Library now takes reservations on this form. Submit your details, then collect the book on the date you choose.
        </p>
        <div class="hero-actions">
            <a href="{{ route('reservations.create') }}" class="btn btn-primary btn-lg glow-orange">
                Reserve a book
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </a>
            <a href="{{ route('reservations.index') }}" class="btn btn-ghost">View reservations</a>
        </div>
        <div class="stats">
            <div>
                <p class="stat-value">1 min</p>
                <p class="stat-label">To fill the form</p>
            </div>
            <div>
                <p class="stat-value">8pm</p>
                <p class="stat-label">Desk closes weekdays</p>
            </div>
            <div>
                <p class="stat-value accent">Nakawa</p>
                <p class="stat-label">Main campus library</p>
            </div>
        </div>
    </div>
</section>
<!-- End: Hero -->

<!-- Start: How it works -->
<section id="how-it-works" class="how">
    <div class="container">
        <div class="how-card">
            <div class="how-intro">
                <p class="kicker">How it works</p>
                <h2 class="section-title">Three steps, then collect your book</h2>
            </div>
            <ol class="steps">
                <li class="step">
                    <span class="step-num">01</span>
                    <h3>Fill in the form</h3>
                    <p>Give your name, registration number, and the title you want held at the desk.</p>
                </li>
                <li class="step">
                    <span class="step-num">02</span>
                    <h3>It is saved</h3>
                    <p>The reservation is stored in the library database so it does not vanish on submit.</p>
                </li>
                <li class="step">
                    <span class="step-num">03</span>
                    <h3>Pick it up</h3>
                    <p>Come on your chosen date. Staff already have your name on the list.</p>
                </li>
            </ol>
        </div>
    </div>
</section>
<!-- End: How it works -->

<!-- Start: Call to action -->
<section class="cta">
    <div class="container">
        <div class="cta-band glow-orange">
            <div class="cta-copy">
                <h2>Ready to reserve a book?</h2>
                <p>Use the form to send your details. Open the list any time to see reservations already made.</p>
            </div>
            <div class="cta-actions">
                <a href="{{ route('reservations.create') }}" class="btn btn-ink btn-md">Reserve a book</a>
                <a href="{{ route('reservations.index') }}" class="btn btn-white btn-md">View list</a>
            </div>
        </div>
    </div>
</section>
<!-- End: Call to action -->
@endsection
