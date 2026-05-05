@extends('layouts.admin')

@section('title', 'Possible Matches')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title">Automated Matches</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>Lost</th><th>Found</th><th>Score</th><th>Reasons</th></tr></thead>
                <tbody>
                    @foreach ($matches as $match)
                        <tr><td>{{ $match->lostItem->name }}</td><td>{{ $match->foundItem->name }}</td><td><span class="badge text-bg-success">{{ $match->score }}%</span></td><td>{{ implode(', ', $match->reasons) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $matches->links() }}</div>
    </div>
@endsection
