@extends('layouts.app')
@section('title', 'About the Project')

@section('content')
<section class="bg-forest py-5 mb-5">
    <div class="container-xl text-white">
        <div class="section-kicker text-gold">About Pearl360</div>
        <h1 class="display-5 fw-bold">A 360° Virtual Tour & Smart Trip Planning System for Uganda's National Parks</h1>
        <p class="lead text-white-50 mb-0">A research project by Group 12, Bachelor of Business Computing, Makerere University Business School – case study: Uganda Wildlife Authority (UWA).</p>
    </div>
</section>
<div class="container-xl">
    <div class="row g-5">
        <div class="col-lg-7">
            <h3>The problem</h3>
            <p>Uganda's national parks hold world-class attractions, yet potential tourists find it hard to picture the experience in each park before travelling. Information about parks, activities, difficulty levels and costs is scattered across many websites, and lesser-known parks such as Kidepo Valley, Semuliki and Mount Elgon receive far less attention.</p>
            <h3 class="mt-4">Our solution</h3>
            <ul>
                <li><strong>360° virtual tours</strong> of key attractions in every park, with clickable hotspots and a mobile motion (VR) mode.</li>
                <li><strong>A smart trip planner</strong> – a knowledge-based recommender that scores each park on interests, fitness, budget, travel month and days available.</li>
                <li><strong>Centralised park information</strong>, an interactive map and side-by-side comparison.</li>
                <li><strong>Online booking</strong> with verified tour operators, <strong>Mobile Money / card payments</strong> and <strong>SMS + email notifications</strong>.</li>
                <li><strong>Analytics</strong> for UWA and operators: most viewed parks and scenes, popular interests, bookings and revenue.</li>
            </ul>
            <h3 class="mt-4">Research objectives</h3>
            <ol>
                <li>To examine how tourists currently search for information about and plan visits to Uganda's national parks.</li>
                <li>To identify the user and system requirements for a virtual tour and smart trip planning system.</li>
                <li>To design and develop a web-based system with 360° virtual tours, personalised recommendations, centralised park information and booking features.</li>
                <li>To test and validate the system with tourists, tour operators and UWA staff.</li>
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
