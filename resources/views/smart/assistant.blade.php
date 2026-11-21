@extends('layouts.app')
@section('title', 'AI travel assistant')

@section('content')
<section class="bg-forest py-5">
    <div class="container-xl text-white">
        <div class="section-kicker text-gold">AI travel assistant</div>
        <h1 class="fw-bold mb-2">Ask anything about your visit</h1>
        <p class="mb-0 opacity-75" style="max-width:720px">The assistant answers questions about Murchison Falls National Park and recommends activities that suit you.</p>
    </div>
</section>
<div class="container-xl py-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm"><div class="card-body chat-box" id="mf-chat" data-url="{{ route('assistant.ask') }}">
                <div class="chat-log" aria-live="polite">
                    <div class="msg bot">Hello! I am your Murchison Falls travel assistant. Ask me about fees, activities, the best time to visit, getting there, booking or what to pack.</div>
                </div>
                <div class="d-flex flex-wrap gap-2 my-2">
                    @foreach (['How many days do I need?', 'How much is the boat trip to the falls?', 'When is the best time to visit?', 'Where can I see the shoebill?', 'Can children track chimpanzees?', 'What should I pack?'] as $q)
                        <button type="button" class="btn btn-sm btn-outline-forest rounded-pill" data-q="{{ $q }}">{{ $q }}</button>
                    @endforeach
                </div>
                <form class="d-flex gap-2">
                    <input type="text" class="form-control" placeholder="Ask about Murchison Falls…" aria-label="Your question" maxlength="500">
                    <button class="btn btn-gold px-4">Send</button>
                </form>
            </div></div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm"><div class="card-body small">
                <h2 class="h6 fw-bold">Good to know</h2>
                <ul class="ps-3 mb-0">
                    <li>{{ $aiEnabled ? 'Answers are written by an AI model (Claude) using the park information in this system.' : 'Answers come from the park information in this system. Add ANTHROPIC_API_KEY to .env to switch on AI answers.' }} Prices are estimates — confirm with UWA.</li>
                    <li>For a complete timed plan, use the <a href="{{ route('plan.index') }}">Smart itinerary</a>.</li>
                    <li>For costs, use the <a href="{{ route('budget') }}">Travel budget</a>; for the forecast, see <a href="{{ route('updates') }}">Travel updates</a>.</li>
                    <li>Do not share passwords or payment details in the chat.</li>
                </ul>
            </div></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')<script src="{{ asset('js/murchison.js') }}?v=1"></script>@endpush
