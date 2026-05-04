@extends('layouts.admin')

@section('title', 'Devices')

@section('content')
    <div class="card content-card">
        <div class="card-header"><h3 class="card-title">Registered Devices</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>Name</th><th>Identifier</th><th>Tracking</th><th>Lost</th><th>Latest</th></tr></thead>
                <tbody>
                    @foreach ($devices as $device)
                        <tr>
                            <td><a href="{{ route('admin.devices.show', $device) }}">{{ $device->name }}</a></td>
                            <td>{{ $device->device_identifier }}</td>
                            <td><span class="badge {{ $device->tracking_enabled ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $device->tracking_enabled ? 'Enabled' : 'Disabled' }}</span></td>
                            <td><span class="badge {{ $device->is_lost ? 'text-bg-danger' : 'text-bg-success' }}">{{ $device->is_lost ? 'Lost' : 'Safe' }}</span></td>
                            <td>{{ $device->latestLocation?->latitude }}, {{ $device->latestLocation?->longitude }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $devices->links() }}</div>
    </div>
@endsection
