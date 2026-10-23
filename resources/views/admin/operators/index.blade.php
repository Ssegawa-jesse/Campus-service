@extends('layouts.app')
@section('title', 'Tour operators')
@section('dashboard', true)

@section('content')
<div class="page-head">
    <h1>Tour operators</h1>
    <a href="{{ route('admin.users.create') }}" class="btn btn-gold"><i class="bi bi-plus-lg"></i> Add operator</a>
</div>
<p class="text-muted small">Every operator who registers appears under <strong>Pending</strong>. Approve them to make their packages visible and let tourists book them; they are notified by SMS and email either way.</p>

<ul class="nav nav-pills mb-3 gap-1">
    @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $key => $label)
        <li class="nav-item">
            <a class="nav-link {{ $status === $key ? 'active bg-forest' : 'text-forest' }}" href="{{ route('admin.operators.index', ['status' => $key]) }}">
                {{ $label }}
                <span class="badge {{ $key === 'pending' && ($counts['pending'] ?? 0) ? 'bg-danger' : 'bg-secondary' }} ms-1">{{ $key === 'all' ? $counts->sum() : ($counts[$key] ?? 0) }}</span>
            </a>
        </li>
    @endforeach
</ul>

<div class="card border-0 shadow-sm"><div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead><tr><th>Company</th><th>Contact</th><th>Licence</th><th>Parks</th><th class="text-center">Packages</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
            @forelse ($operators as $o)
                <tr class="{{ $o->isPending() ? 'table-warning' : '' }}">
                    <td>
                        <strong>{{ $o->company_name }}</strong>
                        <div class="small text-muted">{{ $o->user->name }} · registered {{ $o->created_at->diffForHumans() }}</div>
                    </td>
                    <td class="small">{{ $o->contact_phone }}<br>{{ $o->contact_email }}</td>
                    <td class="small">{{ $o->licence_no ?: '–' }}</td>
                    <td class="small">{{ $o->parks->pluck('name')->map(fn ($n) => Str::before($n, ' National'))->implode(', ') ?: '–' }}</td>
                    <td class="text-center">{{ $o->packages_count }}</td>
                    <td>
                        <span class="badge bg-{{ $o->statusColor() }}">{{ $o->statusLabel() }}</span>
                        @if ($o->status === 'approved' && $o->approved_at)<div class="small text-muted">{{ $o->approved_at->format('d M Y') }}{{ $o->approver ? ' by '.$o->approver->name : '' }}</div>@endif
                        @if ($o->status === 'rejected' && $o->rejection_reason)<div class="small text-muted">{{ Str::limit($o->rejection_reason, 60) }}</div>@endif
                    </td>
                    <td class="text-end" style="min-width:230px">
                        @include('admin.operators._actions')
                        @if ($o->status === 'approved')<a href="{{ route('operators.show', $o) }}" target="_blank" class="small d-inline-block mt-1">Public page <i class="bi bi-box-arrow-up-right"></i></a>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No operators in this list.</td></tr>
            @endforelse
        </tbody>
    </table>
</div></div>
@endsection
