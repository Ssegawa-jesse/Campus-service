@extends('layouts.app')
@section('title', 'Copies of '.$book->title)
@section('sidebar') @include('partials.admin-nav') @endsection
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div class="d-flex align-items-center gap-3"><div class="thumb" style="width:60px;flex-basis:60px">@include('partials.cover', ['book' => $book])</div>
        <div><h3 class="mb-0">{{ $book->title }}</h3><span class="text-muted">{{ $book->authorNames() }}</span></div></div>
    <div>
        <a href="{{ route('admin.copies.labels', ['book' => $book->id]) }}" target="_blank" class="btn btn-outline-primary"><i class="bi bi-printer"></i> Print QR labels</a>
        <a href="{{ route('admin.books.edit', $book) }}" class="btn btn-outline-secondary">Edit book</a>
    </div>
</div>
<form method="POST" action="{{ route('admin.copies.store', $book) }}" class="card card-body shadow-sm mb-3">@csrf
    <div class="row g-2 align-items-end">
        <div class="col-md-2"><label class="form-label small">Quantity</label><input type="number" name="quantity" value="1" min="1" max="100" class="form-control"></div>
        <div class="col-md-4"><label class="form-label small">Shelf location</label><input name="shelf_location" class="form-control" value="{{ $book->copies->last()?->shelf_location }}"></div>
        <div class="col-md-3"><label class="form-label small">Condition</label>
            <select name="condition" class="form-select">@foreach(\App\Models\Copy::CONDITIONS as $c)<option>{{ $c }}</option>@endforeach</select></div>
        <div class="col-md-3 d-grid"><button class="btn btn-lib"><i class="bi bi-plus-lg"></i> Add copies</button></div>
    </div>
</form>
<div class="card shadow-sm">
    <table class="table mb-0">
        <thead><tr><th>QR</th><th>Code</th><th>Shelf</th><th>Condition</th><th>Status</th><th>Borrower</th><th></th></tr></thead>
        <tbody>
        @forelse($book->copies as $copy)
            <tr>
                <td>{!! \App\Support\Qr::svg($copy->code, 48) !!}</td>
                <td><code>{{ $copy->code }}</code></td>
                <td colspan="3">
                    <form method="POST" action="{{ route('admin.copies.update', $copy) }}" class="row g-1">@csrf @method('PUT')
                        <div class="col"><input name="shelf_location" value="{{ $copy->shelf_location }}" class="form-control form-control-sm"></div>
                        <div class="col"><select name="condition" class="form-select form-select-sm">@foreach(\App\Models\Copy::CONDITIONS as $c)<option @selected($copy->condition === $c)>{{ $c }}</option>@endforeach</select></div>
                        <div class="col">
                            <select name="status" class="form-select form-select-sm" @disabled(in_array($copy->status, ['on_loan', 'on_hold']))>
                                @foreach(array_unique(['available', 'lost', 'damaged', $copy->status]) as $s)<option value="{{ $s }}" @selected($copy->status === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>@endforeach
                            </select>
                            @if(in_array($copy->status, ['on_loan', 'on_hold']))<input type="hidden" name="status" value="{{ $copy->status }}">@endif
                        </div>
                        <div class="col-auto"><button class="btn btn-sm btn-outline-primary"><i class="bi bi-check"></i></button></div>
                    </form>
                </td>
                <td>{{ $copy->currentLoan?->member->user->name ?? '—' }}@if($copy->currentLoan)<br><small class="text-muted">due {{ $copy->currentLoan->due_at->format('d M') }}</small>@endif</td>
                <td class="text-end">
                    <form method="POST" action="{{ route('admin.copies.destroy', $copy) }}" onsubmit="return confirm('Delete this copy?')">@csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-muted text-center py-4">No copies. Add some above.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
