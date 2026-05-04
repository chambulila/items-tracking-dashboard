@extends('layouts.admin')

@section('content')
    <h1 class="mb-4">Users</h1>
    <table class="table table-striped bg-white">
        <thead><tr><th>Name</th><th>Email</th><th>Roles</th></tr></thead>
        <tbody>
            @foreach ($users as $user)
                <tr><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->roles->pluck('label')->join(', ') }}</td></tr>
            @endforeach
        </tbody>
    </table>
    {{ $users->links() }}
@endsection
