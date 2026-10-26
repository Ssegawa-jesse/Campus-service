@extends('layouts.app')
@section('title', 'Categories')
@section('sidebar') @include('partials.admin-nav') @endsection
@section('content')
<h3 class="mb-3">Categories</h3>
<form method="POST" action="{{ route('admin.categories.store') }}" class="card card-body shadow-sm mb-3">@csrf
    <div class="row g-2">
        <div class="col-md-6"><input name="name" class="form-control" placeholder="New category name" required></div>
        <div class="col-md-4"><select name="parent_id" class="form-select"><option value="">No parent</option>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
        <div class="col-md-2 d-grid"><button class="btn btn-lib">Add</button></div>
    </div>
</form>
<div class="card shadow-sm">
    <table class="table mb-0">
        <thead><tr><th>Name</th><th>Parent</th><th>Books</th><th></th></tr></thead>
        <tbody>
        @foreach($categories as $category)
            <tr>
                <td colspan="2">
                    <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="row g-1">@csrf @method('PUT')
                        <div class="col"><input name="name" value="{{ $category->name }}" class="form-control form-control-sm"></div>
                        <div class="col"><select name="parent_id" class="form-select form-select-sm"><option value="">No parent</option>
                            @foreach($categories->where('id', '!=', $category->id) as $c)<option value="{{ $c->id }}" @selected($category->parent_id === $c->id)>{{ $c->name }}</option>@endforeach</select></div>
                        <div class="col-auto"><button class="btn btn-sm btn-outline-primary"><i class="bi bi-check"></i></button></div>
                    </form>
                </td>
                <td>{{ $category->books_count }}</td>
                <td class="text-end"><form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Delete category?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
