@extends('layouts.admin')

@section('title', $device->name)

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Latest Device Location</h3>
                </div>
                <div class="card-body">
                    <div class="ratio ratio-21x9">
                        <div id="map" class="rounded border bg-white"></div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-primary mt-3">
                <div class="card-header">
                    <h3 class="card-title">Location History</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Recorded</th>
                                <th>Latitude</th>
                                <th>Longitude</th>
                                <th>Accuracy</th>
                                <th>Speed</th>
                                <th>Battery</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($device->locations as $location)
                                <tr>
                                    <td>{{ $location->recorded_at->format('D, M j, Y g:i:s A') }}</td>
                                    <td>{{ $location->latitude }}</td>
                                    <td>{{ $location->longitude }}</td>
                                    <td>{{ $location->accuracy ?? '-' }}</td>
                                    <td>{{ $location->speed ?? '-' }}</td>
                                    <td>{{ $location->battery_level !== null ? $location->battery_level.'%' : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No location updates have been received.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Device Details</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Owner</dt>
                        <dd class="col-sm-7">{{ $device->user->name }}</dd>
                        <dt class="col-sm-5">Type</dt>
                        <dd class="col-sm-7">{{ str($device->device_type ?? 'unknown')->title() }}</dd>
                        <dt class="col-sm-5">Identifier</dt>
                        <dd class="col-sm-7">{{ $device->device_identifier }}</dd>
                        <dt class="col-sm-5">Brand / Model</dt>
                        <dd class="col-sm-7">{{ $device->brand_model ?? '-' }}</dd>
                        <dt class="col-sm-5">Serial / IMEI</dt>
                        <dd class="col-sm-7">{{ $device->serial_imei ?? '-' }}</dd>
                        <dt class="col-sm-5">Lost Report</dt>
                        <dd class="col-sm-7">
                            @if ($device->lostItem)
                                <a href="{{ route('admin.lost-items.show', $device->lostItem) }}">{{ $device->lostItem->name }}</a>
                            @else
                                <span class="text-muted">Not linked</span>
                            @endif
                        </dd>
                        <dt class="col-sm-5">Tracking</dt>
                        <dd class="col-sm-7"><span class="badge {{ $device->tracking_enabled ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $device->tracking_enabled ? 'Enabled' : 'Disabled' }}</span></dd>
                        <dt class="col-sm-5">Mode</dt>
                        <dd class="col-sm-7"><span class="badge text-bg-info">{{ str($device->tracking_mode)->title() }}</span></dd>
                        <dt class="col-sm-5">Lost</dt>
                        <dd class="col-sm-7"><span class="badge {{ $device->is_lost ? 'text-bg-danger' : 'text-bg-success' }}">{{ $device->is_lost ? 'Lost' : 'Safe' }}</span></dd>
                        <dt class="col-sm-5">Permission</dt>
                        <dd class="col-sm-7">{{ str($device->location_permission_status)->title() }}</dd>
                        <dt class="col-sm-5">Battery</dt>
                        <dd class="col-sm-7">{{ $device->last_battery_level !== null ? $device->last_battery_level.'%' : '-' }}</dd>
                        <dt class="col-sm-5">Last Seen</dt>
                        <dd class="col-sm-7">{{ $device->last_seen_at?->format('D, M j, Y g:i:s A') ?? 'Never' }}</dd>
                        <dt class="col-sm-5">Connection</dt>
                        <dd class="col-sm-7"><span class="badge {{ $device->isOnline() ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $device->isOnline() ? 'Online' : 'Offline' }}</span></dd>
                    </dl>
                </div>
            </div>

            @if (auth()->user()->hasPermission('manage-device-tracking', 'manage-devices'))
                <div class="card card-outline card-warning mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Tracking Controls</h3>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <form method="POST" action="{{ route('admin.devices.enable-active-search', $device) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-success w-100">Enable Active Search Mode</button>
                            </form>
                            <form method="POST" action="{{ route('admin.devices.disable-tracking', $device) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-outline-secondary w-100">Disable Tracking</button>
                            </form>
                            <form method="POST" action="{{ route('admin.devices.mark-recovered', $device) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-outline-success w-100">Mark Device Recovered</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card card-outline card-secondary mt-3">
                <div class="card-header">
                    <h3 class="card-title">Audit History</h3>
                </div>
                <div class="list-group list-group-flush">
                    @forelse ($auditLogs as $auditLog)
                        <div class="list-group-item">
                            <div class="fw-semibold">{{ str($auditLog->action)->replace(['.', '_'], ' ')->title() }}</div>
                            <div class="small text-muted">{{ $auditLog->user?->name ?? 'System' }} · {{ $auditLog->created_at->diffForHumans() }}</div>
                        </div>
                    @empty
                        <div class="list-group-item text-muted">No tracking audit entries yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const latest = @json($device->latestLocation);
        const latestUrl = @json(route('admin.devices.latest-location', $device));
        const map = L.map('map').setView([latest?.latitude ?? -6.7924, latest?.longitude ?? 39.2083], latest ? 16 : 12);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
        const points = [];
        let latestMarker = null;

        function formatLocationTime(value) {
            if (!value) {
                return '';
            }

            return new Date(value).toLocaleString(undefined, {
                weekday: 'short',
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                second: '2-digit',
            });
        }

        function addLocationMarker(location) {
            const marker = L.circleMarker([location.latitude, location.longitude], {
                color: location.tracking_mode === 'live' ? '#dc3545' : '#0d6efd',
                fillColor: location.tracking_mode === 'live' ? '#dc3545' : '#0d6efd',
                fillOpacity: 0.7,
                radius: location.tracking_mode === 'live' ? 7 : 5,
            }).addTo(map).bindPopup(`${location.tracking_mode ?? 'heartbeat'} - ${formatLocationTime(location.recorded_at)}`);
            points.push([Number(location.latitude), Number(location.longitude)]);
            return marker;
        }

        @foreach ($device->locations as $location)
            addLocationMarker(@json($location));
        @endforeach

        if (points.length > 1) {
            L.polyline(points, { color: '#198754', weight: 3 }).addTo(map);
        }

        if (latest) {
            latestMarker = L.marker([latest.latitude, latest.longitude]).addTo(map).bindPopup('Latest location');
        }

        async function refreshLatestLocation() {
            const response = await fetch(latestUrl, { headers: { Accept: 'application/json' } });
            if (!response.ok) {
                return;
            }
            const payload = await response.json();
            const location = payload.latest_location;
            if (!location) {
                return;
            }
            const latLng = [Number(location.latitude), Number(location.longitude)];
            if (latestMarker) {
                latestMarker.setLatLng(latLng);
            } else {
                latestMarker = L.marker(latLng).addTo(map).bindPopup('Latest location');
            }
        }

        setInterval(refreshLatestLocation, 10000);
    </script>
@endpush
