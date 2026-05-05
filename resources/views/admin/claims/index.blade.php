@extends('layouts.admin')

@section('title', 'Claims')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title">Claim Verification Queue</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>Item</th><th>Claimant</th><th>Status</th><th>Proof</th><th>Decision</th></tr></thead>
                <tbody>
                    @forelse ($claims as $claim)
                        <tr>
                            <td><a href="{{ route('admin.found-items.show', $claim->foundItem) }}">{{ $claim->foundItem->name }}</a></td>
                            <td>{{ $claim->claimant->name }}</td>
                            <td><span class="badge text-bg-secondary">{{ str($claim->status)->title() }}</span></td>
                            <td>{{ $claim->proof_description }}</td>
                            <td>
                                @if (auth()->user()->hasPermission('verify-claims', 'manage-lost-found') && $claim->status === 'pending')
                                    @include('admin.claims.partials.verify-buttons', ['claim' => $claim])
                                @else
                                    {{ $claim->decision_notes ?? '-' }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No claims found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $claims->links() }}</div>
    </div>
@endsection
