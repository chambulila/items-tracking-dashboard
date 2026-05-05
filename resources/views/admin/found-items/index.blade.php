@extends('layouts.admin')

@section('title', 'Found Items')

@section('content')
    <div class="card content-card mb-3">
        <div class="card-header d-flex align-items-center">
            <h3 class="card-title mb-0">Search Found Items</h3>
            <a href="{{ route('admin.found-items.create') }}" class="btn btn-success btn-sm ms-auto"><i class="bi bi-plus-lg"></i> Report Found Item</a>
        </div>
        <div class="card-body">
            @include('admin.partials.item-filters', ['route' => route('admin.found-items')])
        </div>
    </div>

    @include('admin.partials.item-table', ['items' => $items, 'type' => 'found'])
@endsection
