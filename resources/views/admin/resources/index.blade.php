@extends('layouts.app')
@section('title', 'Past papers & e-resources')
@section('sidebar') @include('partials.admin-nav') @endsection
@section('content')
<h3 class="mb-3">Past papers &amp; e-resources</h3>
<form method="POST" action="{{ route('admin.resources.store') }}" enctype="multipart/form-data" class="card card-body shadow-sm mb-3">@csrf
    <div class="row g-2">
        <div class="col-md-4"><input name="title" class="form-control" placeholder="Title, e.g. Financial Accounting Exam" required></div>
        <div class="col-md-3"><input name="course" class="form-control" placeholder="Course / code"></div>
        <div class="col-md-1"><input type="number" name="year" class="form-control" placeholder="Year"></div>
        <div class="col-md-2"><select name="type" class="form-select">@foreach(\App\Models\DigitalResource::TYPES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
        <div class="col-md-2"><input type="file" name="file" class="form-control" required></div>
    </div>
    <div class="mt-2"><button class="btn btn-lib"><i class="bi bi-upload"></i> Upload</button> <small class="text-muted">PDF, Word, PowerPoint, EPUB or ZIP up to 20 MB.</small></div>
</form>
<div class="card shadow-sm">
    <table class="table mb-0">
        <thead><tr><th>Title</th><th>Course</th><th>Year</th><th>Type</th><th>Downloads</th><th>Uploaded by</th><th></th></tr></thead>
        <tbody>
        @forelse($resources as $r)
            <tr><td>{{ $r->title }}</td><td>{{ $r->course }}</td><td>{{ $r->year }}</td><td>{{ $r->typeLabel() }}</td><td>{{ $r->downloads }}</td><td>{{ $r->uploader?->name }}</td>
                <td class="text-end text-nowrap"><a href="{{ route('resources.download', $r) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></a>
                    <form method="POST" action="{{ route('admin.resources.destroy', $r) }}" class="d-inline" onsubmit="return confirm('Delete this file?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td></tr>
        @empty
            <tr><td colspan="7" class="text-muted text-center py-4">Nothing uploaded yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $resources->links('pagination::bootstrap-5') }}</div>
@endsection
