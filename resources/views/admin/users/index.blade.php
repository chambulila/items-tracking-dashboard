@extends('layouts.admin')

@section('title', 'Users')

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card content-card">
        <div class="card-header d-flex align-items-center">
            <h3 class="card-title mb-0">User Management</h3>
            <a href="{{ route('admin.users.create') }}" class="btn btn-success btn-sm ms-auto">
                <i class="bi bi-plus-lg"></i> New User
            </a>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Roles</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone ?? '-' }}</td>
                            <td>{{ $user->roles->pluck('label')->join(', ') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary btn-sm">Edit</a>
                                @if (! auth()->user()->is($user))
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-inline" onsubmit="return confirm('Delete this user?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $users->links() }}</div>
    </div>
@endsection
