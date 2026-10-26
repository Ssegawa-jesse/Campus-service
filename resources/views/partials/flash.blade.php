@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show no-print"><i class="bi bi-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show no-print">
        <i class="bi bi-exclamation-triangle"></i>
        @if($errors->count() === 1) {{ $errors->first() }} @else
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
