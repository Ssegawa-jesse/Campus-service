@extends('layouts.app')
@section('title', 'Manage parks')
@section('dashboard', true)

@section('content')
<div class="page-head"><h1>Parks, media & 360° tours</h1><a href="{{ route('admin.parks.create') }}" class="btn btn-gold"><i class="bi bi-plus-lg"></i> Add park</a></div>
<div class="card border-0 shadow-sm"><div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead><tr><th>Park</th><th>Region</th><th class="text-center">Activities</th><th class="text-center">Photos/videos</th><th class="text-center">360° scenes</th><th class="text-center">Views</th><th></th></tr></thead>
        <tbody>
            @foreach ($parks as $park)
                <tr>
                    <td><div class="d-flex gap-2 align-items-center"><img src="{{ $park->coverUrl() }}" width="60" height="40" class="rounded object-fit-cover" alt=""><div><strong>{{ $park->name }}</strong>@unless ($park->is_published)<span class="badge bg-secondary ms-1">Draft</span>@endunless</div></div></td>
                    <td class="small">{{ $park->region }}</td>
                    <td class="text-center">{{ $park->activities_count }}</td>
                    <td class="text-center"><a href="{{ route('admin.media.index', $park) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-images"></i> {{ $park->media_count }}</a></td>
                    <td class="text-center"><a href="{{ route('admin.scenes.index', $park) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-badge-vr"></i> {{ $park->scenes_count }}</a></td>
                    <td class="text-center small">{{ number_format($park->views_count) }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('parks.show', $park) }}" class="btn btn-sm btn-link" target="_blank" title="View"><i class="bi bi-box-arrow-up-right"></i></a>
                        <a href="{{ route('admin.parks.edit', $park) }}" class="btn btn-sm btn-forest">Edit</a>
                        <form method="POST" action="{{ route('admin.parks.destroy', $park) }}" class="d-inline" data-confirm="Delete {{ $park->name }} and ALL its scenes, photos and bookings?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div></div>
@endsection
