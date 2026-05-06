@extends('layouts.admin')

@section('title', 'Devices')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header d-flex align-items-center">
            <h3 class="card-title mb-0">Registered Devices</h3>
            <div class="ms-auto btn-group">
                <a href="{{ route('admin.devices') }}" class="btn btn-sm {{ $showingLostOnly ? 'btn-outline-primary' : 'btn-primary' }}">All Devices</a>
                <a href="{{ route('admin.devices', ['lost' => 1]) }}" class="btn btn-sm {{ $showingLostOnly ? 'btn-primary' : 'btn-outline-primary' }}">Lost Devices</a>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Owner</th>
                        <th>Type</th>
                        <th>Identifier</th>
                        <th>Tracking</th>
                        <th>Mode</th>
                        <th>Lost</th>
                        <th>Permission</th>
                        <th>Latest</th>
                        <th>Last Seen</th>
                        <th>Connection</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($devices as $device)
                        <tr>
                            <td><a href="{{ route('admin.devices.show', $device) }}">{{ $device->name }}</a></td>
                            <td>{{ $device->user->name }}</td>
                            <td>{{ str($device->device_type ?? 'unknown')->title() }}</td>
                            <td>{{ $device->device_identifier }}</td>
                            <td><span class="badge {{ $device->tracking_enabled ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $device->tracking_enabled ? 'Enabled' : 'Disabled' }}</span></td>
                            <td><span class="badge text-bg-info">{{ str($device->tracking_mode)->title() }}</span></td>
                            <td><span class="badge {{ $device->is_lost ? 'text-bg-danger' : 'text-bg-success' }}">{{ $device->is_lost ? 'Lost' : 'Safe' }}</span></td>
                            <td>{{ str($device->location_permission_status)->title() }}</td>
                            <td>
                                @if ($device->latitude !== null && $device->longitude !== null)
                                    {{ $device->latitude }}, {{ $device->longitude }}
                                @else
                                    <span class="text-muted">No location</span>
                                @endif
                            </td>
                            <td>{{ $device->last_seen_at?->diffForHumans() ?? 'Never' }}</td>
                            <td><span class="badge {{ $device->isOnline() ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $device->isOnline() ? 'Online' : 'Offline' }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">No devices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $devices->links() }}</div>
    </div>
@endsection
