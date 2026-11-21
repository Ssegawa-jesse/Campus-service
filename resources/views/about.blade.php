@extends('layouts.app')
@section('title', 'About the Project')

@section('content')
<section class="bg-forest py-5 mb-5">
    <div class="container-xl text-white">
        <div class="section-kicker text-gold">About the project</div>
        <h1 class="display-5 fw-bold">A Web-based Smart Trip Planning System with 360° Virtual Exploration for Murchison Falls National Park</h1>
        <p class="lead text-white-50 mb-0">A project by Group 12, Bachelor of Business Computing, Makerere University Business School – case study: Uganda Wildlife Authority (UWA).</p>
    </div>
</section>
<div class="container-xl">
    <div class="row g-5">
        <div class="col-lg-7">
            <h3>The problem</h3>
            <p>Visitors to Murchison Falls National Park find it hard to plan a good trip. Each activity has its own best time and place – game drives at dawn on the northern bank across the ferry, boat trips from Paraa, chimpanzee tracking in the south – and information on fees, seasons and routes is spread over several websites. There was no tool that turns a visitor's interests and days into a timed plan with a cost.</p>
            <h3 class="mt-4">Our solution</h3>
            <ul>
                <li><strong>Smart itinerary (main feature)</strong> – a timed day-by-day plan from the visitor's dates, interests, fitness, budget level and pace, with reasons, a packing list, the weather and a cost estimate.</li>
                <li><strong>Travel budgeting</strong> in US dollars and Uganda shillings.</li>
                <li><strong>Smart navigation</strong> – a map of attractions and routes with directions.</li>
                <li><strong>Virtual exploration</strong> – 360° scenes with hotspots for the activities in the park.</li>
                <li><strong>AI travel assistant</strong> and <strong>travel updates</strong> with a live forecast.</li>
                <li><strong>Booking</strong> with approved tour operators, <strong>Mobile Money / card payments</strong> and <strong>SMS notifications</strong>.</li>
            </ul>
            <h3 class="mt-4">Project objectives</h3>
            <ol>
                <li>To study and analyse how visitors plan trips to Murchison Falls National Park, and the challenges they face.</li>
                <li>To identify the user and system requirements for a smart trip planning system for the park.</li>
                <li>To design and develop the web-based smart trip planning system.</li>
                <li>To test and validate the system with visitors, tour operators and UWA staff.</li>
            </ol>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5>Project team – Group 12</h5>
                    <ul class="list-unstyled mb-0 small">
                        <li class="py-1 border-bottom">Nayiga Sharlot</li>
                        <li class="py-1 border-bottom">Ssegawa Jesse</li>
                        <li class="py-1 border-bottom">Akech Daniella</li>
                        <li class="py-1 border-bottom">Mawanda Jabel</li>
                        <li class="py-1">Niyomukunzi H. Adeline</li>
                    </ul>
                </div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5>Technology</h5>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach (['Laravel (PHP)', 'MySQL', 'Bootstrap 5', 'Pannellum 360°', 'Leaflet + OpenStreetMap', 'Chart.js', 'Flutterwave (MoMo & cards)', 'Africa\'s Talking SMS'] as $t)
                            <span class="chip">{{ $t }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
