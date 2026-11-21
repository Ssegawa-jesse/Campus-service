@extends('layouts.app')
@section('title', 'SMS & email logs')
@section('dashboard', true)

@section('content')
<div class="page-head"><h1><i class="bi bi-send-check"></i> SMS & email notification logs</h1></div>
<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h5>Notification simulator</h5>
            <p class="small text-muted">SMS driver: <code>{{ $smsDriver }}</code> · Mail driver: <code>{{ $mailDriver }}</code>. With <code>log</code> drivers nothing leaves the server; messages are stored here, which is ideal for the research demo.</p>
            <form method="POST" action="{{ route('admin.notifications.test') }}" class="row g-2">@csrf
                <div class="col-12"><select name="channel" class="form-select form-select-sm"><option value="both">SMS + email</option><option value="sms">SMS only</option><option value="email">Email only</option></select></div>
                <div class="col-12"><input name="email" type="email" value="{{ $adminEmail }}" class="form-control form-control-sm"></div>
                <div class="col-12"><input name="phone" value="+{{ ltrim($adminPhone, '+') }}" class="form-control form-control-sm"></div>
                <div class="col-12"><input name="message" class="form-control form-control-sm" placeholder="SMS text (optional)"></div>
                <div class="col-12"><button class="btn btn-gold btn-sm">Send test</button></div>
            </form>
        </div></div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100"><div class="card-body small">
            <h5>When notifications are sent</h5>
            <ul class="mb-0">
                <li>Registration (welcome + admin alert)</li>
                <li>New booking or Smart Itinerary booking (tourist, operator, admin)</li>
                <li>Operator quote / confirmation / decline</li>
                <li><strong>Payment received</strong> (MTN MoMo, Airtel Money, Visa/Mastercard) – instant confirmation to the tourist and to the admin contact {{ $adminEmail }} / +{{ ltrim($adminPhone, '+') }}</li>
                <li>Operator approval or rejection</li>
            </ul>
        </div></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-6">
        <h5>SMS log</h5>
        <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-sm small mb-0">
            <thead><tr><th>When</th><th>To</th><th>Message</th><th>Status</th></tr></thead>
            @forelse ($sms as $l)<tr><td class="text-nowrap">{{ $l->created_at->format('d M H:i') }}</td><td>{{ $l->to }}</td><td>{{ $l->message }}</td><td><span class="badge bg-{{ ['sent' => 'success', 'logged' => 'info', 'failed' => 'danger'][$l->status] ?? 'secondary' }}">{{ $l->status }}</span></td></tr>
            @empty<tr><td colspan="4" class="text-muted">No SMS yet.</td></tr>@endforelse
        </table></div></div>
        <div class="mt-2">{{ $sms->links() }}</div>
    </div>
    <div class="col-xl-6">
        <h5>Email log</h5>
        <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-sm small mb-0">
            <thead><tr><th>When</th><th>To</th><th>Subject</th><th>Status</th></tr></thead>
            @forelse ($emails as $l)<tr><td class="text-nowrap">{{ $l->created_at->format('d M H:i') }}</td><td>{{ $l->to }}</td><td title="{{ $l->body }}">{{ $l->subject }}@if ($l->error)<div class="text-danger">{{ Str::limit($l->error, 80) }}</div>@endif</td><td><span class="badge bg-{{ ['sent' => 'success', 'logged' => 'info', 'failed' => 'danger'][$l->status] ?? 'secondary' }}">{{ $l->status }}</span></td></tr>
            @empty<tr><td colspan="4" class="text-muted">No emails yet.</td></tr>@endforelse
        </table></div></div>
        <div class="mt-2">{{ $emails->links() }}</div>
    </div>
</div>
@endsection
