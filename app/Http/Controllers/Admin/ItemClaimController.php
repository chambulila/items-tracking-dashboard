<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FoundItem;
use App\Models\ItemClaim;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ItemClaimController extends Controller
{
    public function index(Request $request): View
    {
        $claims = ItemClaim::query()
            ->with(['foundItem', 'claimant'])
            ->when(! $request->user()->hasPermission('verify-claims', 'manage-lost-found'), fn ($query) => $query->where('claimant_id', $request->user()->id))
            ->latest()
            ->paginate(25);

        return view('admin.claims.index', [
            'claims' => $claims,
        ]);
    }

    public function store(Request $request, FoundItem $foundItem, AuditLogger $auditLogger): RedirectResponse
    {
        abort_if($foundItem->status !== 'unclaimed', 422, 'This item is no longer available for claims.');
        abort_if($foundItem->finder_id === $request->user()->id, 403);

        $data = $request->validate([
            'proof_description' => ['required', 'string', 'min:10'],
        ]);

        $claim = ItemClaim::query()->create([
            'found_item_id' => $foundItem->id,
            'claimant_id' => $request->user()->id,
            'proof_description' => $data['proof_description'],
            'status' => 'pending',
        ]);

        $auditLogger->log('claim.submitted', $request->user(), $claim, ['found_item_id' => $foundItem->id]);

        return redirect()->route('admin.found-items.show', $foundItem)->with('status', 'Claim submitted for verification.');
    }

    public function verify(Request $request, ItemClaim $claim, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'decision_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $claim->update([
            'status' => $data['status'],
            'decision_notes' => $data['decision_notes'] ?? null,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        if ($data['status'] === 'approved') {
            $claim->foundItem()->update(['status' => 'claimed']);
        }

        $auditLogger->log('claim.'.$data['status'], $request->user(), $claim, ['found_item_id' => $claim->found_item_id]);

        return back()->with('status', 'Claim '.$data['status'].'.');
    }
}
