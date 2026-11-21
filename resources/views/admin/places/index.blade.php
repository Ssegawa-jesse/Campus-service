@extends('layouts.app')
@section('title', 'Map places & gates')
@section('dashboard', true)

@section('content')
<div class="page-head"><h1><i class="bi bi-geo-alt"></i> Map places & gates</h1><a href="{{ route('admin.places.create') }}" class="btn btn-gold"><i class="bi bi-plus-lg"></i> Add place</a></div>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Name</th><th>Type</th><th>Bank / zone</th><th>Coordinates</th><th>Activities</th><th></th></tr></thead>
    @foreach ($places as $p)
        <tr class="{{ $p->is_active ? '' : 'text-muted' }}">
            <td><strong>{{ $p->name }}</strong>@unless ($p->is_active) <span class="badge bg-secondary">hidden</span>@endunless</td>
            <td class="small">{{ $p->typeLabel() }}</td><td class="small">{{ \App\Models\Place::ZONES[$p->zone] ?? $p->zone }}</td>
            <td class="small">{{ $p->latitude }}, {{ $p->longitude }}</td><td>{{ $p->activities_count }}</td>
            <td class="text-nowrap"><a href="{{ route('admin.places.edit', $p) }}" class="btn btn-sm btn-outline-forest">Edit</a>
                <form method="POST" action="{{ route('admin.places.destroy', $p) }}" class="d-inline" data-confirm="Remove this place?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td>
        </tr>
    @endforeach
</table></div></div>
@endsection
