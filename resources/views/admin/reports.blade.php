@extends('layouts.admin')

@section('title', 'Reports')

@section('content')
    <div class="card content-card">
        <div class="card-header"><h3 class="card-title">Reporting and Analytics</h3></div>
        <div class="card-body">
            <p class="text-muted">Use <code>/api/analytics</code> with date, campus, category, and status filters to feed dashboard charts.</p>
            <canvas class="w-100 rounded bg-white border" height="120"></canvas>
        </div>
    </div>
@endsection
