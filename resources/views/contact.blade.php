@extends('layouts.app')
@section('title', 'Contact Us')

@section('content')
<div class="container-xl py-5">
    <div class="row g-5">
        <div class="col-lg-5">
            <div class="section-kicker">Get in touch</div>
            <h1 class="section-title">We'd love to hear from you</h1>
            <p class="text-muted">Questions about a park, a booking, or partnering as a tour operator? Send us a message.</p>
            <div class="fact mb-3"><i class="bi bi-envelope"></i><div><div class="small text-muted">Email</div><a href="mailto:{{ setting('admin_email', config('pearl360.admin_email')) }}">{{ setting('admin_email', config('pearl360.admin_email')) }}</a></div></div>
            <div class="fact mb-3"><i class="bi bi-telephone"></i><div><div class="small text-muted">Phone / SMS</div><a href="tel:+{{ ltrim(setting('admin_phone', config('pearl360.admin_phone')), '+') }}">+{{ ltrim(setting('admin_phone', config('pearl360.admin_phone')), '+') }}</a></div></div>
            <div class="fact mb-3"><i class="bi bi-whatsapp"></i><div><div class="small text-muted">WhatsApp</div><a href="https://wa.me/{{ ltrim(setting('support_whatsapp', config('pearl360.admin_phone')), '+') }}" target="_blank" rel="noopener">Chat with us</a></div></div>
            <div class="fact"><i class="bi bi-geo-alt"></i><div><div class="small text-muted">Location</div>Kampala, Uganda</div></div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('contact.send') }}" class="row g-3">
                        @csrf
                        <div class="col-md-6"><label class="form-label">Your name</label><input name="name" class="form-control" value="{{ old('name', auth()->user()?->name) }}" required></div>
                        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email', auth()->user()?->email) }}" required></div>
                        <div class="col-md-6"><label class="form-label">Phone (optional)</label><input name="phone" class="form-control" value="{{ old('phone', auth()->user()?->phone) }}"></div>
                        <div class="col-md-6"><label class="form-label">Subject</label><input name="subject" class="form-control" value="{{ old('subject') }}" required></div>
                        <div class="col-12"><label class="form-label">Message</label><textarea name="message" rows="6" class="form-control" required>{{ old('message') }}</textarea></div>
                        <div class="col-12"><button class="btn btn-forest px-4"><i class="bi bi-send me-1"></i> Send message</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
