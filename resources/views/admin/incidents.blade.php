@extends('layouts.admin')

@section('content')
    <h1 class="mb-4">Incidents</h1>
    <table class="table table-striped bg-white">
        <thead><tr><th>Category</th><th>Severity</th><th>Status</th><th>Campus</th><th>Assignee</th></tr></thead>
        <tbody>
            @foreach ($incidents as $incident)
                <tr><td>{{ $incident->category->name }}</td><td>{{ $incident->severity }}</td><td>{{ $incident->status }}</td><td>{{ $incident->campus->name }}</td><td>{{ $incident->assignee?->name }}</td></tr>
            @endforeach
        </tbody>
    </table>
    {{ $incidents->links() }}
@endsection
