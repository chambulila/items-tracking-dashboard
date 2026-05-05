@extends('layouts.admin')

@section('title', 'Notifications')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0">My Notifications</h3>
            <button type="button" class="btn btn-sm btn-outline-success" id="notificationPanelTogglePage">Open activity panel</button>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Title</th>
                        <th>Message</th>
                        <th>Created By</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($notifications as $notification)
                        <tr @class(['table-success' => is_null($notification->read_at)])>
                            <td>
                                <span class="badge text-bg-{{ $notification->level === 'danger' ? 'danger' : ($notification->level === 'warning' ? 'warning' : ($notification->level === 'success' ? 'success' : 'info')) }}">
                                    {{ str($notification->category ?? $notification->type)->replace('_', ' ')->title() }}
                                </span>
                            </td>
                            <td>{{ $notification->title }}</td>
                            <td>{{ $notification->message }}</td>
                            <td>{{ $notification->creator?->name ?? 'System' }}</td>
                            <td>{{ $notification->read_at?->diffForHumans() ?? 'Unread' }}</td>
                            <td>
                                @if ($notification->action_url)
                                    <a href="{{ $notification->action_url }}" class="btn btn-sm btn-outline-primary">View</a>
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No notifications yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $notifications->links() }}</div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('notificationPanelTogglePage')?.addEventListener('click', () => {
            document.getElementById('notificationPanelToggle')?.click();
        });
    </script>
@endpush
