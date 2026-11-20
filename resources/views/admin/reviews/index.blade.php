@extends('layouts.app')
@section('title', 'Reviews')
@section('dashboard', true)

@section('content')
<div class="page-head"><h1>Review moderation</h1></div>
<div class="card border-0 shadow-sm"><div class="table-responsive">
    <table class="table align-middle mb-0 small">
        <thead><tr><th>User</th><th>About</th><th>Rating</th><th>Comment</th><th>Date</th><th>Status</th><th></th></tr></thead>
        <tbody>
            @forelse ($reviews as $r)
                <tr>
                    <td>{{ $r->user->name }}</td>
                    <td>{{ $r->park?->name ?? $r->operator?->company_name }}</td>
                    <td>@include('partials.stars', ['rating' => $r->rating])</td>
                    <td style="max-width:320px">{{ $r->comment }}</td>
                    <td>{{ $r->created_at->format('d M Y') }}</td>
                    <td>{!! $r->is_approved ? '<span class="badge bg-success">Visible</span>' : '<span class="badge bg-secondary">Hidden</span>' !!}</td>
                    <td class="text-nowrap">
                        <form method="POST" action="{{ route('admin.reviews.toggle', $r) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary">{{ $r->is_approved ? 'Hide' : 'Approve' }}</button></form>
                        <form method="POST" action="{{ route('admin.reviews.destroy', $r) }}" class="d-inline" data-confirm="Delete review?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No reviews.</td></tr>
            @endforelse
        </tbody>
    </table>
</div></div>
<div class="mt-3">{{ $reviews->links() }}</div>
@endsection
