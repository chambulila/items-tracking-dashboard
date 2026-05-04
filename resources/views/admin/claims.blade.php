@extends('layouts.admin')

@section('content')
    <h1 class="mb-4">Claim Verification</h1>
    <table class="table table-striped bg-white">
        <thead><tr><th>Item</th><th>Claimant</th><th>Status</th><th>Proof</th></tr></thead>
        <tbody>
            @foreach ($claims as $claim)
                <tr><td>{{ $claim->foundItem->name }}</td><td>{{ $claim->claimant->name }}</td><td>{{ $claim->status }}</td><td>{{ $claim->proof_description }}</td></tr>
            @endforeach
        </tbody>
    </table>
    {{ $claims->links() }}
@endsection
