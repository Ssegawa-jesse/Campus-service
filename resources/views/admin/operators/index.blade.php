@extends('layouts.app')
@section('title', 'Tour operators')
@section('dashboard', true)

@section('content')
<div class="page-head"><h1>Tour operators</h1><a href="{{ route('admin.users.create') }}" class="btn btn-gold"><i class="bi bi-plus-lg"></i> Add operator</a></div>
<div class="card border-0 shadow-sm"><div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead><tr><th>Company</th><th>Contact</th><th>Licence</th><th>Parks</th><th class="text-center">Packages</th><th class="text-center">Bookings</th><th>Status</th><th></th></tr></thead>
        <tbody>
            @foreach ($operators as $o)
                <tr>
                    <td><strong>{{ $o->company_name }}</strong><div class="small text-muted">{{ $o->user->name }} · joined {{ $o->created_at->format('d M Y') }}</div></td>
                    <td class="small">{{ $o->contact_phone }}<br>{{ $o->contact_email }}</td>
                    <td class="small">{{ $o->licence_no ?: '–' }}</td>
                    <td class="small">{{ $o->parks->pluck('name')->map(fn ($n) => Str::before($n, ' National'))->implode(', ') ?: '–' }}</td>
                    <td class="text-center">{{ $o->packages_count }}</td>
                    <td class="text-center">{{ $o->bookings_count }}</td>
                    <td>{!! $o->is_verified ? '<span class="badge bg-success">Verified</span>' : '<span class="badge bg-warning text-dark">Pending</span>' !!}</td>
                    <td class="text-nowrap">
                        <form method="POST" action="{{ route('admin.operators.verify', $o) }}" class="d-inline">@csrf
                            <button class="btn btn-sm {{ $o->is_verified ? 'btn-outline-secondary' : 'btn-forest' }}">{{ $o->is_verified ? 'Un-verify' : 'Verify' }}</button>
                        </form>
                        @if ($o->is_verified)<a href="{{ route('operators.show', $o) }}" target="_blank" class="btn btn-sm btn-link"><i class="bi bi-box-arrow-up-right"></i></a>@endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div></div>
@endsection
