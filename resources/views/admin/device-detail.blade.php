@extends('layouts.admin')

@section('title', $device->name)

@section('content')
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
@endsection

@push('scripts')
    <script>
        const latest = @json($device->latestLocation);
        const map = L.map('map').setView([latest?.latitude ?? -6.7924, latest?.longitude ?? 39.2083], latest ? 16 : 12);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
        @foreach ($device->locations as $location)
            L.marker([{{ $location->latitude }}, {{ $location->longitude }}]).addTo(map);
        @endforeach
    </script>
@endpush
