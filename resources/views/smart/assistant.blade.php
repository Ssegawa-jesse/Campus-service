@extends('layouts.app')
@section('title', 'AI Travel Assistant')

@section('content')
<section class="bg-forest py-4">
    <div class="container-xl">
        <div class="section-kicker text-gold">Module 4</div>
        <h1 class="fw-bold mb-1">AI Travel Assistant</h1>
        <p class="text-white-50 mb-0">Ask about Murchison Falls weather, the best season, packing, UWA rules, fees, the ferry, gates – or ask for an itinerary for your budget and number of days.</p>
    </div>
</section>
<div class="container-xl py-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm"><div class="card-body">
                <div id="chat" class="chat-box" aria-live="polite">
                    <div class="chat-msg bot">Hello! I'm the Murchison Falls travel assistant. How can I help you plan your trip?</div>
                </div>
                <form id="chatForm" class="d-flex gap-2 mt-3">
                    <input id="q" class="form-control" placeholder="e.g. Plan 3 days for $1,200 with chimps" maxlength="1000" autocomplete="off" required>
                    <button class="btn btn-gold"><i class="bi bi-send"></i></button>
                </form>
            </div></div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3"><div class="card-body">
                <h6>Try asking</h6>
                @foreach (['What is the best time to visit?', 'What should I pack?', 'What are the UWA rules in the park?', 'How much is the boat cruise for East Africans?', 'When does the Paraa ferry run?', 'Plan 3 days with $1,500 for wildlife and chimps', 'Plan 2 days for UGX 1,500,000 east african'] as $s)
                    <button class="btn btn-sm btn-outline-forest mb-2 me-1 suggest">{{ $s }}</button>
                @endforeach
            </div></div>
            <div class="alert {{ $aiEnabled ? 'alert-success' : 'alert-secondary' }} small">
                <i class="bi bi-cpu"></i>
                @if ($aiEnabled)
                    Answers come from <strong>Claude (Anthropic)</strong>, grounded in this platform's live Murchison Falls data. If the AI is unavailable, the built-in knowledge base answers.
                @else
                    Running on the <strong>built-in knowledge base</strong>, which uses live park data. Add <code>ANTHROPIC_API_KEY</code> to <code>.env</code> to turn on generative AI answers.
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const chat = document.getElementById('chat'), form = document.getElementById('chatForm'), q = document.getElementById('q');
    const history = [];
    const add = (role, text, extra = '') => {
        const d = document.createElement('div');
        d.className = 'chat-msg ' + (role === 'user' ? 'user' : 'bot');
        d.textContent = text;
        if (extra) d.insertAdjacentHTML('beforeend', extra);
        chat.appendChild(d); chat.scrollTop = chat.scrollHeight; return d;
    };
    async function ask(text) {
        add('user', text);
        const wait = add('bot', '…');
        try {
            const r = await fetch(@json(route('assistant.ask')), { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ question: text, history }) });
            const d = await r.json();
            wait.remove();
            if (!r.ok) { add('bot', d.message || 'Sorry, something went wrong.'); return; }
            add('bot', d.answer, `<div class="small text-muted mt-1">${d.source === 'claude' ? 'AI (Claude)' : 'Knowledge base'}${d.plan_link ? ` · <a href="${d.plan_link}">Open Smart Itinerary</a>` : ''}</div>`);
            history.push({ role: 'user', content: text }, { role: 'assistant', content: d.answer });
        } catch (e) { wait.remove(); add('bot', 'Network error – please try again.'); }
    }
    form.addEventListener('submit', e => { e.preventDefault(); const t = q.value.trim(); if (t) { q.value = ''; ask(t); } });
    document.querySelectorAll('.suggest').forEach(b => b.addEventListener('click', () => ask(b.textContent)));
})();
</script>
@endpush
