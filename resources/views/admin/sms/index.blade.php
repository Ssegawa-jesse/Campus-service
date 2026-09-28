@extends('layouts.app')
@section('title', 'SMS centre')
@section('dashboard', true)

@section('content')
<div class="page-head"><h1>SMS centre</h1><span class="badge bg-{{ $driver === 'log' ? 'warning text-dark' : 'success' }}">Driver: {{ $driver }}</span></div>
@if ($driver === 'log')
    <div class="alert alert-warning small"><i class="bi bi-info-circle"></i> SMS are only <strong>logged</strong> (not delivered). To send real SMS in Uganda, create an <a href="https://africastalking.com" target="_blank" rel="noopener">Africa's Talking</a> account, then set <code>SMS_DRIVER=africastalking</code>, <code>AT_USERNAME</code>, <code>AT_API_KEY</code> and <code>AT_SANDBOX=false</code> in <code>.env</code>.</div>
@endif
<div class="card border-0 shadow-sm mb-4"><div class="card-body">
    <h5>Send SMS</h5>
    <form method="POST" action="{{ route('admin.sms.send') }}" class="row g-2">
        @csrf
        <div class="col-md-3"><select name="audience" class="form-select" id="aud"><option value="single">One number</option><option value="tourists">All tourists (opted-in)</option><option value="operators">All operators</option></select></div>
        <div class="col-md-3" id="toWrap"><input name="to" class="form-control" placeholder="2567XXXXXXXX" value="{{ setting('admin_phone', config('pearl360.admin_phone')) }}"></div>
        <div class="col-md-6"><div class="input-group"><input name="message" class="form-control" maxlength="459" placeholder="Message" required><button class="btn btn-forest"><i class="bi bi-send"></i></button></div></div>
    </form>
</div></div>
<div class="card border-0 shadow-sm"><div class="table-responsive">
    <table class="table mb-0 small align-middle">
        <thead><tr><th>To</th><th>Message</th><th>Driver</th><th>Status</th><th>Time</th></tr></thead>
        <tbody>
            @forelse ($logs as $l)
                <tr><td>{{ $l->to }}</td><td style="max-width:420px">{{ $l->message }}</td><td>{{ $l->driver }}</td>
                    <td><span class="badge bg-{{ ['sent' => 'success', 'logged' => 'info', 'failed' => 'danger'][$l->status] ?? 'secondary' }}" title="{{ $l->response }}">{{ $l->status }}</span></td>
                    <td class="text-nowrap">{{ $l->created_at->format('d M H:i') }}</td></tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No SMS yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div></div>
<div class="mt-3">{{ $logs->links() }}</div>
@endsection

@push('scripts')
<script>const a = document.getElementById('aud'), tw = document.getElementById('toWrap'); a.addEventListener('change', () => tw.classList.toggle('d-none', a.value !== 'single'));</script>
@endpush
