@extends('layouts.admin')

@section('title', $title)

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title">{{ $title }}</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>Name</th><th>Category</th><th>Campus</th><th>Status</th><th>Coordinates</th></tr></thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->category->name }}</td>
                            <td>{{ $item->campus->name }}</td>
                            <td><span class="badge text-bg-secondary">{{ $item->status }}</span></td>
                            <td>{{ $item->latitude }}, {{ $item->longitude }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $items->links() }}</div>
    </div>
@endsection
