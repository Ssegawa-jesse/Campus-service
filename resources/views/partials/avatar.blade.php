{{-- Profile picture, or the user's initials. --}}
@php($px = $size ?? 40)
@if($user->photoUrl())
    <img src="{{ $user->photoUrl() }}" alt="{{ $user->name }}" class="avatar {{ $class ?? '' }}" style="width:{{ $px }}px;height:{{ $px }}px">
@else
    <span class="avatar {{ $class ?? '' }}" style="width:{{ $px }}px;height:{{ $px }}px;font-size:{{ round($px * .38) }}px">{{ $user->initials() }}</span>
@endif
