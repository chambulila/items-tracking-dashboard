@extends('layouts.admin')

@section('content')
    <h1 class="mb-4">Dashboard</h1>
    <div class="row g-3">
        @foreach ([['Lost Items', $lostCount], ['Found Items', $foundCount], ['Recovered', $recoveredCount], ['Incidents', $incidentCount]] as [$label, $value])
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="text-muted">{{ $label }}</div>
                        <div class="display-6">{{ $value }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
