@extends('layouts.app')
@section('title', 'Settings')
@section('sidebar') @include('partials.admin-nav') @endsection
@section('content')
<h3 class="mb-3">Loan rules, fines &amp; integrations</h3>
<div class="row g-4">
    <div class="col-lg-7">
        <form method="POST" action="{{ route('admin.settings.update') }}" class="card card-body shadow-sm">@csrf @method('PUT')
            @foreach($labels as $key => $label)
                <div class="row mb-2 align-items-center">
                    <label class="col-sm-8 col-form-label">{{ $label }}</label>
                    <div class="col-sm-4"><input name="{{ $key }}" value="{{ old($key, $settings[$key]) }}" class="form-control" {{ $key === 'library_name' ? '' : 'type=number min=0' }} required></div>
                </div>
            @endforeach
            <div><button class="btn btn-lib">Save settings</button></div>
        </form>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm mb-3"><div class="card-body">
            <h6>SMS (Africa's Talking)</h6>
            <p class="mb-1">Status: {!! $smsLive ? '<span class="badge bg-success">Connected</span>' : '<span class="badge bg-secondary">Log only (no credentials)</span>' !!}</p>
            <p class="small text-muted mb-0">Set <code>AFRICASTALKING_USERNAME</code>, <code>AFRICASTALKING_API_KEY</code> and optionally <code>AFRICASTALKING_FROM</code> in the <code>.env</code> file. Use username <code>sandbox</code> for testing.</p>
        </div></div>
        <div class="card shadow-sm mb-3"><div class="card-body">
            <h6>Payments (Flutterwave — MTN MoMo, Airtel Money, card)</h6>
            <p class="mb-1">Status: {!! $paymentsLive ? '<span class="badge bg-success">Connected</span>' : '<span class="badge bg-warning text-dark">Sandbox simulator</span>' !!}</p>
            <p class="small text-muted mb-0">Set <code>FLUTTERWAVE_PUBLIC_KEY</code> and <code>FLUTTERWAVE_SECRET_KEY</code> in <code>.env</code> (test keys work for demonstrations).</p>
        </div></div>
        <div class="card shadow-sm"><div class="card-body">
            <h6>Daily reminders</h6>
            <p class="small text-muted mb-0">Reminders, overdue notices, fine updates and expiry of uncollected reservations run every day at 08:00 via <code>php artisan library:daily</code>. On the server add a cron entry: <code>* * * * * php /path/to/artisan schedule:run</code>.</p>
        </div></div>
    </div>
</div>
@endsection
