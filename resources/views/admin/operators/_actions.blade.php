{{-- Approve / reject / revoke buttons for one operator. Needs $o. --}}
<div class="d-flex flex-wrap gap-1 justify-content-end">
    @if ($o->status !== 'approved')
        <form method="POST" action="{{ route('admin.operators.approve', $o) }}">@csrf
            <button class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Approve</button>
        </form>
    @endif
    @if ($o->status !== 'rejected')
        <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="collapse" data-bs-target="#reject-{{ $o->id }}"><i class="bi bi-x-lg"></i> Reject</button>
    @endif
    @if ($o->status !== 'pending')
        <form method="POST" action="{{ route('admin.operators.revoke', $o) }}" data-confirm="Move {{ $o->company_name }} back to pending?">@csrf
            <button class="btn btn-sm btn-outline-secondary" title="Back to pending"><i class="bi bi-arrow-counterclockwise"></i></button>
        </form>
    @endif
</div>
@if ($o->status !== 'rejected')
    <form method="POST" action="{{ route('admin.operators.reject', $o) }}" class="collapse mt-2" id="reject-{{ $o->id }}">@csrf
        <div class="input-group input-group-sm">
            <input name="reason" class="form-control" placeholder="Reason (sent to the operator)" maxlength="500">
            <button class="btn btn-danger">Confirm reject</button>
        </div>
    </form>
@endif
