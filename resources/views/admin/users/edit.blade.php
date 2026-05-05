@extends('layouts.admin')

@section('title', 'Edit User')

@section('content')
    <div class="card content-card">
        <div class="card-header"><h3 class="card-title">Edit {{ $user->name }}</h3></div>
        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @method('PUT')
            @include('admin.users.partials.form', ['submitLabel' => 'Update User'])
        </form>
    </div>
@endsection
