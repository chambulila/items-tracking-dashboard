@extends('layouts.admin')

@section('content')
    <h1 class="mb-4">Reports</h1>
    <p class="text-muted">Use <code>/api/analytics</code> with date, campus, category, and status filters to feed dashboard charts.</p>
    <canvas class="w-100 rounded bg-white border" height="120"></canvas>
@endsection
