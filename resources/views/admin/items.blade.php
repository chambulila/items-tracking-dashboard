@extends('layouts.admin')

@section('content')
    <h1 class="mb-4">{{ $title }}</h1>
    <table class="table table-striped bg-white">
        <thead><tr><th>Name</th><th>Category</th><th>Campus</th><th>Status</th><th>Coordinates</th></tr></thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->category->name }}</td>
                    <td>{{ $item->campus->name }}</td>
                    <td>{{ $item->status }}</td>
                    <td>{{ $item->latitude }}, {{ $item->longitude }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $items->links() }}
@endsection
