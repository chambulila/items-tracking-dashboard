@extends('layouts.admin')

@section('content')
    <h1 class="mb-4">{{ $device->name }}</h1>
    <div id="map" style="height: 420px" class="rounded border bg-white"></div>
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
