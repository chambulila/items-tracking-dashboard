@extends('layouts.admin')

@section('title', 'Report Found Item')

@section('content')
    <div class="card content-card">
        <div class="card-header"><h3 class="card-title">Report Found Item</h3></div>
        <form method="POST" action="{{ route('admin.found-items.store') }}" enctype="multipart/form-data">
            @include('admin.partials.item-form', ['dateField' => 'found_date', 'dateLabel' => 'Date Found', 'submitLabel' => 'Submit Found Item Report'])
        </form>
    </div>
@endsection
