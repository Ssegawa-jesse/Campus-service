@extends('layouts.app')
@section('title', 'Past papers & e-resources')
@section('content')
<h3 class="mb-3"><i class="bi bi-file-earmark-pdf"></i> Past examination papers &amp; e-resources</h3>
<form class="card card-body shadow-sm mb-4">
    <div class="row g-2">
        <div class="col-md-6"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search by title or course"></div>
        <div class="col-md-3">
            <select name="type" class="form-select">
                <option value="">All types</option>
                @foreach(\App\Models\DigitalResource::TYPES as $key => $label)<option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="year" class="form-select">
                <option value="">Any year</option>
                @foreach($years as $y)<option @selected(request('year') == $y)>{{ $y }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-1 d-grid"><button class="btn btn-lib"><i class="bi bi-search"></i></button></div>
    </div>
</form>
@guest<div class="alert alert-info small"><a href="{{ route('login') }}">Log in</a> or <a href="{{ route('register') }}">register</a> to download files.</div>@endguest
<div class="card shadow-sm">
    <table class="table table-hover mb-0">
        <thead><tr><th>Title</th><th>Course</th><th>Year</th><th>Type</th><th class="text-end">Downloads</th><th></th></tr></thead>
        <tbody>
        @forelse($resources as $r)
            <tr>
                <td>{{ $r->title }}</td><td>{{ $r->course ?? '—' }}</td><td>{{ $r->year ?? '—' }}</td>
                <td><span class="badge bg-light text-dark border">{{ $r->typeLabel() }}</span></td>
                <td class="text-end">{{ $r->downloads }}</td>
                <td class="text-end">@auth<a href="{{ route('resources.download', $r) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></a>@endauth</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No resources found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $resources->links('pagination::bootstrap-5') }}</div>
@endsection
