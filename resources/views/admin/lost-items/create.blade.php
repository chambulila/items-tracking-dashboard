@extends('layouts.admin')

@section('title', 'Report Lost Item')

@section('content')
    <div class="card content-card">
        <div class="card-header"><h3 class="card-title">Report Lost Item</h3></div>
        <form method="POST" action="{{ route('admin.lost-items.store') }}" enctype="multipart/form-data">
            @include('admin.partials.item-form', ['dateField' => 'lost_date', 'dateLabel' => 'Date Lost', 'submitLabel' => 'Submit Lost Item Report'])
        </form>
    </div>
@endsection
