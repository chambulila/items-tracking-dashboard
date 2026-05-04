@extends('layouts.admin')

@section('content')
    <h1 class="mb-4">Notification Panel</h1>
    <table class="table table-striped bg-white">
        <thead><tr><th>User</th><th>Type</th><th>Title</th><th>Read</th><th>Email</th></tr></thead>
        <tbody>
            @foreach ($notifications as $notification)
                <tr>
                    <td>{{ $notification->user->email }}</td>
                    <td>{{ $notification->type }}</td>
                    <td>{{ $notification->title }}</td>
                    <td>{{ $notification->read_at?->toDayDateTimeString() ?? 'Unread' }}</td>
                    <td>{{ $notification->emailed_at?->toDayDateTimeString() ?? 'Pending' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $notifications->links() }}
@endsection
