@extends('layouts.admin')

@section('content')
    <h1 class="mb-4">Possible Matches</h1>
    <table class="table table-striped bg-white">
        <thead><tr><th>Lost</th><th>Found</th><th>Score</th><th>Reasons</th></tr></thead>
        <tbody>
            @foreach ($matches as $match)
                <tr><td>{{ $match->lostItem->name }}</td><td>{{ $match->foundItem->name }}</td><td>{{ $match->score }}</td><td>{{ implode(', ', $match->reasons) }}</td></tr>
            @endforeach
        </tbody>
    </table>
    {{ $matches->links() }}
@endsection
