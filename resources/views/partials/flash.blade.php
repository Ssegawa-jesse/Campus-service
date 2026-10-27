@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show no-print d-flex gap-2"><i class="bi bi-check-circle-fill"></i><div>{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show no-print d-flex gap-2">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
            @if($errors->count() === 1) {{ $errors->first() }} @else
                <ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            @endif
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
