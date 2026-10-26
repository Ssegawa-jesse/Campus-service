@extends('layouts.app')
@section('title', 'My reservations')
@section('content')
<h3 class="mb-3">My reservations</h3>
<div class="card shadow-sm">
    <table class="table mb-0">
        <thead><tr><th>Title</th><th>Reserved</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($reservations as $r)
            <tr>
                <td><a href="{{ route('catalog.show', $r->book) }}">{{ $r->book->title }}</a></td>
                <td>{{ $r->created_at->format('d M Y') }}</td>
                <td>
                    @switch($r->status)
                        @case('ready')<span class="badge bg-success">Ready — collect by {{ $r->expires_at->format('d M Y') }}</span>@break
                        @case('waiting')<span class="badge bg-secondary">Waiting · #{{ $r->position() }} in line</span>@break
                        @default<span class="badge bg-light text-dark border">{{ ucfirst($r->status) }}</span>
                    @endswitch
                </td>
                <td class="text-end">
                    @if($r->isOpen())
                        <form method="POST" action="{{ route('my.reservations.destroy', $r) }}" onsubmit="return confirm('Cancel this reservation?')">@csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Cancel</button></form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-muted text-center py-3">You have not reserved any books. Reserve books that are on loan from the catalogue.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $reservations->links('pagination::bootstrap-5') }}</div>
@endsection
