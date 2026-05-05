<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersItems;
use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Campus;
use App\Models\FoundItem;
use App\Models\ItemCategory;
use App\Services\AttachmentService;
use App\Services\AuditLogger;
use App\Services\MatchingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FoundItemController extends Controller
{
    use FiltersItems;

    public function index(Request $request): View
    {
        $items = $this->applyItemFilters(
            FoundItem::query()->with(['category', 'campus', 'building', 'finder', 'attachments']),
            $request,
            'found_date'
        )
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.found-items.index', [
            'items' => $items,
            'categories' => ItemCategory::query()->orderBy('name')->get(),
            'campuses' => Campus::query()->orderBy('name')->get(),
            'statuses' => ['unclaimed', 'claimed', 'released', 'closed'],
        ]);
    }

    public function create(): View
    {
        return view('admin.found-items.create', $this->formData(new FoundItem));
    }

    public function store(Request $request, AttachmentService $attachments, MatchingService $matching, AuditLogger $auditLogger): RedirectResponse
    {
        $item = $request->user()->foundItems()->create($this->validatedData($request));
        $attachments->storeMany($item, $request->file('attachments', []), $request->user());
        $matching->matchFoundItem($item);
        $auditLogger->log('found_item.created', $request->user(), $item);

        return redirect()->route('admin.found-items.show', $item)->with('status', 'Found item report submitted.');
    }

    public function show(FoundItem $foundItem, MatchingService $matching): View
    {
        if ($foundItem->status === 'unclaimed') {
            $matching->matchFoundItem($foundItem, notify: false);
        }

        return view('admin.found-items.show', [
            'item' => $foundItem->refresh()->load(['category', 'campus', 'building', 'finder', 'attachments', 'claims.claimant', 'matches.lostItem']),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(FoundItem $item): array
    {
        return [
            'item' => $item,
            'categories' => ItemCategory::query()->orderBy('name')->get(),
            'campuses' => Campus::query()->orderBy('name')->get(),
            'buildings' => Building::query()->with('campus')->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
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
            'found_date' => ['required', 'date'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);
    }
}
