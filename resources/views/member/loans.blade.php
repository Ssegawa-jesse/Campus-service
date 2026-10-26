@extends('layouts.app')
@section('title', 'My loans')
@section('content')
<h3 class="mb-3">My loans</h3>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white"><strong>Currently borrowed</strong></div>
    <table class="table mb-0">
        <thead><tr><th>Title</th><th>Copy</th><th>Borrowed</th><th>Due</th><th>Renewals</th><th></th></tr></thead>
        <tbody>
        @forelse($active as $loan)
            <tr class="{{ $loan->isOverdue() ? 'table-danger' : '' }}">
                <td>{{ $loan->copy->book->title }}</td><td>{{ $loan->copy->code }}</td>
                <td>{{ $loan->issued_at->format('d M Y') }}</td>
                <td>{{ $loan->due_at->format('d M Y') }} @if($loan->isOverdue())<span class="badge bg-danger">Overdue</span>@endif</td>
                <td>{{ $loan->renewals }} / {{ \App\Support\LibrarySettings::int('max_renewals') }}</td>
                <td class="text-end">@unless($loan->isOverdue())<form method="POST" action="{{ route('my.loans.renew', $loan) }}">@csrf<button class="btn btn-sm btn-outline-primary">Renew</button></form>@endunless</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-muted text-center py-3">No books on loan.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="card shadow-sm">
    <div class="card-header bg-white"><strong>Borrowing history</strong></div>
    <table class="table mb-0">
        <thead><tr><th>Title</th><th>Borrowed</th><th>Returned</th><th>Fine</th></tr></thead>
        <tbody>
        @forelse($history as $loan)
            <tr><td>{{ $loan->copy->book->title }}</td><td>{{ $loan->issued_at->format('d M Y') }}</td><td>{{ $loan->returned_at->format('d M Y') }}</td>
                <td>@if($loan->fine) UGX {{ number_format($loan->fine->amount) }} ({{ $loan->fine->status }}) @else — @endif</td></tr>
        @empty
            <tr><td colspan="4" class="text-muted text-center py-3">No history yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $history->links('pagination::bootstrap-5') }}</div>
@endsection
