@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="row g-3">
        @foreach ([['Lost Items', $lostCount, 'text-bg-primary', 'bi-search'], ['Found Items', $foundCount, 'text-bg-success', 'bi-box-seam'], ['Recovered', $recoveredCount, 'text-bg-info', 'bi-check-circle'], ['Incidents', $incidentCount, 'text-bg-warning', 'bi-exclamation-triangle']] as [$label, $value, $theme, $icon])
            <div class="col-md-3">
                <div class="small-box {{ $theme }}">
                    <div class="inner">
                        <h3>{{ $value }}</h3>
                        <p>{{ $label }}</p>
                    </div>
                    <div class="small-box-icon">
                        <i class="bi {{ $icon }}"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
