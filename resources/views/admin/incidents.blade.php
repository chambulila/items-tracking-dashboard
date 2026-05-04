@extends('layouts.admin')

@section('title', 'Incidents')

@section('content')
    <div class="card content-card">
        <div class="card-header"><h3 class="card-title">Incident Lifecycle</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>Category</th><th>Severity</th><th>Status</th><th>Campus</th><th>Assignee</th></tr></thead>
                <tbody>
                    @foreach ($incidents as $incident)
                        <tr><td>{{ $incident->category->name }}</td><td>{{ $incident->severity }}</td><td><span class="badge text-bg-secondary">{{ $incident->status }}</span></td><td>{{ $incident->campus->name }}</td><td>{{ $incident->assignee?->name }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $incidents->links() }}</div>
    </div>
@endsection
