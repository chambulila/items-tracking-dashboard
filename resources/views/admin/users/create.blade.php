@extends('layouts.admin')

@section('title', 'Create User')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title">Create User</h3></div>
        <form method="POST" action="{{ route('admin.users.store') }}">
            @include('admin.users.partials.form', ['submitLabel' => 'Create User'])
        </form>
    </div>
@endsection
