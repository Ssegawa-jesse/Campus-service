@extends('layouts.app')
@section('title', 'Library card')
@section('content')
@php($user = $member->user)
<div class="text-center mb-4 no-print">
    <h3 class="mb-1">Library membership card</h3>
    <p class="text-muted mb-0">Show this card (or its QR code) at the counter to borrow books.</p>
</div>
<div class="d-flex justify-content-center">
    <div class="library-card">
        <div class="lc-head">
            <div class="d-flex align-items-center gap-2">
                <span style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.2);display:grid;place-items:center;font-size:1.2rem"><i class="bi bi-book-half"></i></span>
                <div><div class="org">{{ \App\Support\LibrarySettings::get('library_name') }}</div><div class="sub">Makerere University Business School</div></div>
            </div>
            <span class="badge rounded-pill text-bg-light text-primary text-uppercase">{{ $member->type }}</span>
        </div>
        <div class="lc-body">
            @if($user->photoUrl())
                <img src="{{ $user->photoUrl() }}" alt="{{ $user->name }}" class="lc-photo">
            @else
                <div class="lc-photo">{{ $user->initials() }}</div>
            @endif
            <div class="flex-grow-1 min-w-0">
                <div class="lc-name">{{ $user->name }}</div>
                <dl class="lc-fields">
                    <dt>Course</dt><dd>{{ $member->programme ?: '—' }}</dd>
                    @if($member->type === 'student')<dt>Year</dt><dd>{{ $member->year_of_study ? 'Year '.$member->year_of_study : '—' }}</dd>@endif
                    <dt>Age</dt><dd>{{ $member->age() !== null ? $member->age().' years' : '—' }}</dd>
                    <dt>Phone</dt><dd>{{ $user->phone ?: '—' }}</dd>
                </dl>
            </div>
            <div class="lc-qr">{!! \App\Support\Qr::svg($member->member_no, 120) !!}</div>
        </div>
        <div class="lc-foot">
            <span>Library No. <code>{{ $member->member_no }}</code></span>
            <span>@if($member->expiry_date)Valid until <strong>{{ $member->expiry_date->format('d M Y') }}</strong>@endif</span>
        </div>
    </div>
</div>
<div class="text-center mt-4 no-print d-flex flex-wrap justify-content-center gap-2">
    <button onclick="window.print()" class="btn btn-primary px-4"><i class="bi bi-printer"></i> Print card</button>
    @if(auth()->id() === $user->id)
        <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> Update photo &amp; details</a>
    @else
        <a href="{{ route('admin.members.edit', $member) }}" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> Edit member</a>
    @endif
</div>
@if(! $user->photo || ! $member->programme || ! $member->date_of_birth || ($member->type === 'student' && ! $member->year_of_study))
    <div class="alert alert-warning mt-4 mx-auto no-print" style="max-width:560px">
        <i class="bi bi-info-circle"></i> Some card details are missing
        ({{ collect(['photo' => ! $user->photo, 'course' => ! $member->programme, 'date of birth' => ! $member->date_of_birth, 'year of study' => $member->type === 'student' && ! $member->year_of_study])->filter()->keys()->join(', ') }}).
        @if(auth()->id() === $user->id)<a href="{{ route('profile.edit') }}">Add them on your profile.</a>@endif
    </div>
@endif
@endsection
