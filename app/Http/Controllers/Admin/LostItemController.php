<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersItems;
use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Campus;
use App\Models\ItemCategory;
use App\Models\LostItem;
use App\Services\AttachmentService;
use App\Services\AuditLogger;
use App\Services\MatchingService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LostItemController extends Controller
{
    use FiltersItems;

    public function index(Request $request): View
    {
        $items = $this->applyItemFilters(
            LostItem::query()->with(['category', 'campus', 'building', 'user', 'attachments']),
            $request,
            'lost_date'
        )
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.lost-items.index', [
            'items' => $items,
            'categories' => ItemCategory::query()->orderBy('name')->get(),
            'campuses' => Campus::query()->orderBy('name')->get(),
            'statuses' => ['open', 'recovered', 'closed'],
        ]);
    }

    public function create(): View
    {
        return view('admin.lost-items.create', $this->formData(new LostItem));
    }

    public function store(Request $request, AttachmentService $attachments, MatchingService $matching, AuditLogger $auditLogger, NotificationService $notifications): RedirectResponse
    {
        $item = DB::transaction(function () use ($request, $attachments, $matching, $auditLogger, $notifications): LostItem {
            $item = $request->user()->lostItems()->create($this->validatedData($request));
            $attachments->storeMany($item, $request->file('attachments', []), $request->user());
            $matching->matchLostItem($item);
            $auditLogger->log('lost_item.created', $request->user(), $item);

            $notifications->notifyPermissionHolders(
                permissions: ['view-lost-items', 'manage-lost-found'],
                title: 'New lost item reported',
                message: "A new lost item has been reported: {$item->name}.",
                category: 'lost_item',
                type: 'warning',
                createdBy: $request->user(),
                entityType: LostItem::class,
                entityId: $item->id,
                actionUrl: route('admin.lost-items.show', $item),
            );

            return $item;
        });

        return redirect()->route('admin.lost-items.show', $item)->with('status', 'Lost item report submitted.');
    }

    public function show(Request $request, LostItem $lostItem, MatchingService $matching): View
    {
        if ($lostItem->status === 'open') {
            $matching->matchLostItem($lostItem, notify: false);
        }

        return view('admin.lost-items.show', [
            'item' => $lostItem->refresh()->load(['category', 'campus', 'building', 'user', 'attachments', 'matches.foundItem']),
        ]);
    }

    public function markRecovered(Request $request, LostItem $lostItem, AuditLogger $auditLogger): RedirectResponse
    {
        abort_if(! $request->user()->hasPermission('manage-lost-found') && $lostItem->user_id !== $request->user()->id, 403);

        $lostItem->update(['status' => 'recovered']);
        $auditLogger->log('lost_item.recovered', $request->user(), $lostItem);

        return back()->with('status', 'Lost item marked as recovered.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(LostItem $item): array
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
            'lost_date' => ['required', 'date'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);
    }
}
