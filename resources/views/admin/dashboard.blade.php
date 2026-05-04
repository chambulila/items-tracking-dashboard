@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="row g-3">
        @foreach ([['Lost Items', $lostCount, 'text-bg-primary', 'bi-search'], ['Found Items', $foundCount, 'text-bg-success', 'bi-box-seam'], ['Recovered', $recoveredCount, 'text-bg-info', 'bi-check-circle'], ['Incidents', $incidentCount, 'text-bg-warning', 'bi-exclamation-triangle']] as [$label, $value, $theme, $icon])
            <div class="col-md-3">
                <div class="card content-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted text-uppercase small fw-semibold">{{ $label }}</div>
                                <div class="display-6 fw-bold">{{ $value }}</div>
                            </div>
                            <span class="tracking-stat-icon {{ $theme }}"><i class="bi {{ $icon }} fs-4"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
