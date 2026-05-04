@extends('layouts.admin')

@section('content')
    <h1 class="mb-4">Devices</h1>
    <table class="table table-striped bg-white">
        <thead><tr><th>Name</th><th>Identifier</th><th>Tracking</th><th>Lost</th><th>Latest</th></tr></thead>
        <tbody>
            @foreach ($devices as $device)
                <tr>
                    <td><a href="{{ route('admin.devices.show', $device) }}">{{ $device->name }}</a></td>
                    <td>{{ $device->device_identifier }}</td>
                    <td>{{ $device->tracking_enabled ? 'Yes' : 'No' }}</td>
                    <td>{{ $device->is_lost ? 'Yes' : 'No' }}</td>
                    <td>{{ $device->latestLocation?->latitude }}, {{ $device->latestLocation?->longitude }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $devices->links() }}
@endsection
