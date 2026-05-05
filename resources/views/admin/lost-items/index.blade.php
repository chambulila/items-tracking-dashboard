@extends('layouts.admin')

@section('title', 'Lost Items')

@section('content')
    <div class="card card-outline card-primary mb-3">
        <div class="card-header d-flex align-items-center">
            <h3 class="card-title mb-0">Search Lost Items</h3>
            <a href="{{ route('admin.lost-items.create') }}" class="btn btn-success btn-sm ms-auto"><i class="bi bi-plus-lg"></i> Report Lost Item</a>
        </div>
        <div class="card-body">
            @include('admin.partials.item-filters', ['route' => route('admin.lost-items')])
        </div>
    </div>

    @include('admin.partials.item-table', ['items' => $items, 'type' => 'lost'])
@endsection
