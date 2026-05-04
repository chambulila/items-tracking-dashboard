@extends('layouts.admin')

@section('title', 'Notifications')

@section('content')
    <div class="card content-card">
        <div class="card-header"><h3 class="card-title">Notification Panel</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
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
        </div>
        <div class="card-footer">{{ $notifications->links() }}</div>
    </div>
@endsection
