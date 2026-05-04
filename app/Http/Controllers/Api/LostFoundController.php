<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoundItem;
use App\Models\ItemClaim;
use App\Models\LostItem;
use App\Services\AttachmentService;
use App\Services\AuditLogger;
use App\Services\MatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LostFoundController extends Controller
{
    public function lostIndex(Request $request): JsonResponse
    {
        return response()->json($this->filterItems(LostItem::query()->with(['category', 'campus', 'attachments']), $request)
            ->when(! $request->user()->isPrivileged(), fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest()
            ->paginate(20));
    }

    public function foundIndex(Request $request): JsonResponse
    {
        return response()->json($this->filterItems(FoundItem::query()->with(['category', 'campus', 'attachments']), $request)
            ->when(! $request->user()->isPrivileged(), fn ($query) => $query->where('finder_id', $request->user()->id))
            ->latest()
            ->paginate(20));
    }

    public function storeLost(Request $request, AttachmentService $attachments, MatchingService $matching): JsonResponse
    {
        $data = $this->itemData($request, 'lost_date');
        $item = $request->user()->lostItems()->create($data);

        $attachments->storeMany($item, $request->file('attachments', []), $request->user());
        $matching->matchLostItem($item);

        return response()->json($item->load(['category', 'campus', 'attachments', 'matches']), 201);
    }

    public function storeFound(Request $request, AttachmentService $attachments, MatchingService $matching): JsonResponse
    {
        $data = $this->itemData($request, 'found_date');
        $item = $request->user()->foundItems()->create($data);

        $attachments->storeMany($item, $request->file('attachments', []), $request->user());
        $matching->matchFoundItem($item);

        return response()->json($item->load(['category', 'campus', 'attachments', 'matches']), 201);
    }

    public function claim(Request $request, FoundItem $foundItem): JsonResponse
    {
        $data = $request->validate([
            'proof_description' => ['required', 'string', 'min:10'],
        ]);

        $claim = ItemClaim::query()->create([
            'found_item_id' => $foundItem->id,
            'claimant_id' => $request->user()->id,
            'proof_description' => $data['proof_description'],
            'status' => 'pending',
        ]);

        return response()->json($claim->load('foundItem'), 201);
    }

    public function verifyClaim(Request $request, ItemClaim $claim, AuditLogger $auditLogger): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'decision_notes' => ['nullable', 'string'],
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

        return response()->json($claim->fresh(['foundItem', 'claimant']));
    }

    public function matches(): JsonResponse
    {
        return response()->json(\App\Models\ItemMatch::query()->with(['lostItem', 'foundItem'])->latest('score')->paginate(25));
    }

    private function itemData(Request $request, string $dateField): array
    {
        return $request->validate([
            'item_category_id' => ['required', 'exists:item_categories,id'],
            'campus_id' => ['required', 'exists:campuses,id'],
            'building_id' => ['nullable', 'exists:buildings,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'color' => ['nullable', 'string', 'max:100'],
            'brand_model' => ['nullable', 'string', 'max:255'],
            'serial_imei' => ['nullable', 'string', 'max:255'],
            $dateField => ['required', 'date'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);
    }

    private function filterItems($query, Request $request)
    {
        return $query
            ->when($request->string('keyword')->isNotEmpty(), function ($query) use ($request): void {
                $keyword = '%'.$request->string('keyword')->toString().'%';
                $query->where(fn ($query) => $query->where('name', 'like', $keyword)->orWhere('description', 'like', $keyword));
            })
            ->when($request->filled('category_id'), fn ($query) => $query->where('item_category_id', $request->integer('category_id')))
            ->when($request->filled('campus_id'), fn ($query) => $query->where('campus_id', $request->integer('campus_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')));
    }
}
