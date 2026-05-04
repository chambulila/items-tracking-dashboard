@extends('layouts.admin')

@section('title', 'Claim Verification')

@section('content')
    <div class="card content-card">
        <div class="card-header"><h3 class="card-title">Claim Verification Queue</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>Item</th><th>Claimant</th><th>Status</th><th>Proof</th></tr></thead>
                <tbody>
                    @foreach ($claims as $claim)
                        <tr><td>{{ $claim->foundItem->name }}</td><td>{{ $claim->claimant->name }}</td><td><span class="badge text-bg-secondary">{{ $claim->status }}</span></td><td>{{ $claim->proof_description }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $claims->links() }}</div>
    </div>
@endsection
