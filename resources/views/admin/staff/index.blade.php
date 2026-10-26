@extends('layouts.app')
@section('title', 'Staff accounts')
@section('sidebar') @include('partials.admin-nav') @endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Staff accounts</h3>
    <a href="{{ route('admin.staff.create') }}" class="btn btn-lib"><i class="bi bi-plus-lg"></i> Add staff</a>
</div>
<div class="card shadow-sm">
    <table class="table mb-0">
        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach($staff as $s)
            <tr><td>{{ $s->name }}</td><td>{{ $s->email }}</td><td>{{ $s->phone }}</td>
                <td><span class="badge bg-{{ $s->isAdmin() ? 'dark' : 'primary' }}">{{ $s->isAdmin() ? 'Administrator' : 'Librarian' }}</span></td>
                <td>{{ ucfirst($s->status) }}</td>
                <td class="text-end text-nowrap"><a href="{{ route('admin.staff.edit', $s) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    @unless($s->is(auth()->user()))
                        <form method="POST" action="{{ route('admin.staff.destroy', $s) }}" class="d-inline" onsubmit="return confirm('Delete this account?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                    @endunless</td></tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
