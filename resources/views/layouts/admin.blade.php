<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Tracking Admin' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg bg-dark navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">Tracking Admin</a>
            <div class="navbar-nav">
                <a class="nav-link" href="{{ route('admin.users') }}">Users</a>
                <a class="nav-link" href="{{ route('admin.lost-items') }}">Lost</a>
                <a class="nav-link" href="{{ route('admin.found-items') }}">Found</a>
                <a class="nav-link" href="{{ route('admin.claims') }}">Claims</a>
                <a class="nav-link" href="{{ route('admin.matches') }}">Matches</a>
                <a class="nav-link" href="{{ route('admin.notifications') }}">Notifications</a>
                <a class="nav-link" href="{{ route('admin.devices') }}">Devices</a>
                <a class="nav-link" href="{{ route('admin.incidents') }}">Incidents</a>
                <a class="nav-link" href="{{ route('admin.reports') }}">Reports</a>
            </div>
        </div>
    </nav>
    <main class="container py-4">
        @yield('content')
    </main>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @stack('scripts')
</body>
</html>
