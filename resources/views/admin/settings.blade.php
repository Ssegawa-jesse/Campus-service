@extends('layouts.app')
@section('title', 'Settings')
@section('dashboard', true)

@section('content')
<div class="page-head"><h1>Settings</h1></div>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm"><div class="card-body p-4">
            <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="row g-3">
                @csrf @method('PUT')
                <div class="col-12"><label class="form-label">Site tagline</label><input name="site_tagline" value="{{ old('site_tagline', $settings['site_tagline']) }}" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Admin email (receives alerts)</label><input type="email" name="admin_email" value="{{ old('admin_email', $settings['admin_email']) }}" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Admin phone (receives SMS alerts)</label><input name="admin_phone" value="{{ old('admin_phone', $settings['admin_phone']) }}" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">WhatsApp number</label><input name="support_whatsapp" value="{{ old('support_whatsapp', $settings['support_whatsapp']) }}" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">USD → UGX rate</label><input type="number" step="0.01" name="usd_rate" value="{{ old('usd_rate', $settings['usd_rate']) }}" class="form-control" required></div>
                <div class="col-12"><hr class="my-1"><h6 class="mb-0">Logo</h6></div>
                <div class="col-md-8">
                    <label class="form-label">Site logo <small class="text-muted">(PNG, JPG or WebP, max 2 MB; a square image about 256 × 256 px looks best)</small></label>
                    <input type="file" name="site_logo" accept="image/png,image/jpeg,image/webp" class="form-control">
                    <div class="form-check mt-2"><input type="hidden" name="logo_show_name" value="0"><input class="form-check-input" type="checkbox" name="logo_show_name" value="1" id="lsn" @checked($settings['logo_show_name'] !== '0')><label class="form-check-label" for="lsn">Show the name "Pearl360" next to the logo (untick if your logo already contains the name)</label></div>
                    @if ($settings['site_logo'])
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="rl"><label class="form-check-label" for="rl">Remove my logo and go back to the default</label></div>
                    @endif
                </div>
                <div class="col-md-4 d-flex align-items-center gap-2">
                    <span class="small text-muted">Current:</span>
                    <img src="{{ site_logo() }}" alt="Current logo" style="height:56px;max-width:180px;object-fit:contain" class="border rounded p-1 bg-white">
                </div>
                <div class="col-12"><hr class="my-1"></div>
                <div class="col-md-6"><label class="form-label">Home page hero image</label><input type="file" name="hero_image" accept="image/*" class="form-control">@if ($settings['hero_image'])<img src="{{ media_url($settings['hero_image']) }}" height="60" class="rounded mt-2" alt="">@endif</div>
                <div class="col-md-6"><label class="form-label">Promo video (YouTube)</label><input name="hero_video" value="{{ old('hero_video', $settings['hero_video']) }}" class="form-control"></div>
                <div class="col-12 form-check form-switch ms-2"><input type="hidden" name="auto_approve_reviews" value="0"><input class="form-check-input" type="checkbox" name="auto_approve_reviews" value="1" id="aa" @checked($settings['auto_approve_reviews'] !== '0')><label class="form-check-label" for="aa">Publish reviews immediately (otherwise wait for moderation)</label></div>
                <div class="col-12"><button class="btn btn-forest">Save settings</button></div>
            </form>
        </div></div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm"><div class="card-body small">
            <h6>Integrations (set in .env)</h6>
            <dl class="row mb-0">
                <dt class="col-6">Payments</dt><dd class="col-6"><code>{{ $payment_driver }}</code></dd>
                <dt class="col-6">SMS</dt><dd class="col-6"><code>{{ $sms_driver }}</code></dd>
                <dt class="col-6">Email</dt><dd class="col-6"><code>{{ $mail_driver }}</code></dd>
            </dl>
            <p class="text-muted mt-2 mb-0">See README.md → "Going live" for how to switch on real Mobile Money, SMS and email.</p>
        </div></div>
    </div>
</div>
@endsection
