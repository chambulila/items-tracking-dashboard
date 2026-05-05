@extends('layouts.admin')

@section('title', 'Users')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title">User Management</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>Name</th><th>Email</th><th>Roles</th></tr></thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->roles->pluck('label')->join(', ') }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $users->links() }}</div>
    </div>
@endsection
