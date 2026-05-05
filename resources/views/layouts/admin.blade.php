<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $title ?? trim($__env->yieldContent('title', 'Tracking Admin')) }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    @php
        $navItems = [
            ['route' => 'admin.dashboard', 'icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'permission' => ['view-dashboard']],
            ['route' => 'admin.users.index', 'pattern' => 'admin.users.*', 'icon' => 'bi-people', 'label' => 'Users', 'permission' => ['manage-users']],
            ['route' => 'admin.role-permissions.index', 'pattern' => 'admin.role-permissions.*', 'icon' => 'bi-shield-lock', 'label' => 'Role Permissions', 'permission' => ['manage-role-permissions']],
            ['route' => 'admin.lost-items', 'pattern' => 'admin.lost-items*', 'icon' => 'bi-search', 'label' => 'Lost Items', 'permission' => ['view-lost-items']],
            ['route' => 'admin.found-items', 'pattern' => 'admin.found-items*', 'icon' => 'bi-box-seam', 'label' => 'Found Items', 'permission' => ['view-found-items']],
            ['route' => 'admin.claims', 'pattern' => 'admin.claims*', 'icon' => 'bi-patch-check', 'label' => 'Claims', 'permission' => ['view-claims', 'verify-claims', 'manage-lost-found']],
            ['route' => 'admin.matches', 'icon' => 'bi-shuffle', 'label' => 'Matches', 'permission' => ['view-matches', 'manage-lost-found']],
            ['route' => 'admin.notifications', 'icon' => 'bi-bell', 'label' => 'Notifications', 'permission' => ['view-notifications']],
            ['route' => 'admin.devices', 'icon' => 'bi-phone', 'label' => 'Devices', 'permission' => ['view-devices']],
            ['route' => 'admin.incidents', 'icon' => 'bi-exclamation-triangle', 'label' => 'Incidents', 'permission' => ['view-incidents']],
            ['route' => 'admin.reports', 'icon' => 'bi-bar-chart', 'label' => 'Reports', 'permission' => ['view-analytics']],
        ];
        $pageTitle = trim($__env->yieldContent('title', $title ?? 'Dashboard'));
    @endphp

    <div class="app-wrapper">
        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Toggle sidebar">
                            <i class="bi bi-list"></i>
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <a href="{{ route('admin.dashboard') }}" class="nav-link">Home</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item d-none d-md-block">
                        <span class="nav-link">{{ auth()->user()->name }}</span>
                    </li>
                    <li class="nav-item">
                        <button type="button" id="notificationPanelToggle" class="btn nav-link border-0 position-relative" aria-label="Notifications">
                            <i class="bi bi-bell"></i>
                            <span id="notificationUnreadBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger d-none">0</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn nav-link border-0" aria-label="Logout">
                                <i class="bi bi-box-arrow-right"></i>
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </nav>

        <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
            <div class="sidebar-brand">
                <a href="{{ route('admin.dashboard') }}" class="brand-link text-decoration-none">
                    <span class="brand-image rounded-circle shadow d-inline-flex align-items-center justify-content-center bg-success text-white">
                        <i class="bi bi-geo-alt-fill"></i>
                    </span>
                    <span class="brand-text fw-semibold">Tracking Admin</span>
                </a>
            </div>

            <div class="sidebar-wrapper">
                <nav class="mt-2">
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">
                        @foreach ($navItems as $item)
                            @continue(isset($item['permission']) && ! auth()->user()->hasPermission(...$item['permission']))
                            <li class="nav-item">
                                <a href="{{ route($item['route']) }}" class="nav-link @if (request()->routeIs($item['pattern'] ?? $item['route'])) active @endif">
                                    <i class="nav-icon bi {{ $item['icon'] }}"></i>
                                    <p>{{ $item['label'] }}</p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </div>
        </aside>

        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <h3 class="mb-0">{{ $pageTitle }}</h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                                <li class="breadcrumb-item active" aria-current="page">{{ $pageTitle }}</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    @if (session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">Please check the form and try again.</div>
                    @endif

                    @yield('content')
                </div>
            </div>
        </main>

        <footer class="app-footer">
            <div class="float-end d-none d-sm-inline">Lost Items and Incidents</div>
            <strong>Digital Tracking and Reporting System</strong>
        </footer>
    </div>

    @include('admin.partials.notification-panel')

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        window.notificationRoutes = {
            feed: @json(route('admin.notifications.feed')),
            unreadCount: @json(route('admin.notifications.unread-count')),
            markRead: @json(route('admin.notifications.read', ['notification' => '__ID__'])),
            markAllRead: @json(route('admin.notifications.read-all')),
            index: @json(route('admin.notifications')),
            csrf: @json(csrf_token()),
        };
    </script>
    @stack('scripts')
</body>
</html>
