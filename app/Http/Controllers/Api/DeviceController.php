<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\LostItem;
use App\Services\AuditLogger;
use App\Services\DeviceLocationService;
use App\Services\DeviceTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $devices = Device::query()
            ->with(['latestLocation', 'lostItem'])
            ->when(! $request->user()->hasPermission('manage-devices'), fn ($query) => $query->where('user_id', $request->user()->id))
            ->when($request->boolean('lost'), fn ($query) => $query->where('is_lost', true))
            ->latest()
            ->paginate(20);

        return response()->json($devices);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'device_type' => ['required', Rule::in(Device::TYPES)],
            'device_identifier' => ['required', 'string', 'max:255', 'unique:devices,device_identifier'],
            'brand_model' => ['nullable', 'string', 'max:255'],
            'serial_imei' => ['nullable', 'string', 'max:255'],
            'lost_item_id' => ['nullable', 'exists:lost_items,id'],
        ]);

        $this->authorizeLinkedLostItem($request, $data['lost_item_id'] ?? null);

        $device = $request->user()->devices()->create($data);

        return response()->json($device, 201);
    }

    public function show(Request $request, Device $device): JsonResponse
    {
        $this->authorizeDevice($request, $device);

        return response()->json($device->load(['lostItem.category', 'latestLocation', 'locations' => fn ($query) => $query->latest('recorded_at')->limit(50)]));
    }

    public function updateStatus(Request $request, Device $device, AuditLogger $auditLogger, DeviceTrackingService $tracking): JsonResponse
    {
        abort_if(! $request->user()->hasPermission('manage-device-tracking', 'manage-devices'), 403);

        $data = $request->validate([
            'tracking_enabled' => ['required_without:is_lost', 'boolean'],
            'is_lost' => ['required_without:tracking_enabled', 'boolean'],
        ]);

        $data = $this->trackingState($data);

        $device = $data['tracking_enabled']
            ? $tracking->enableActiveSearch($device, $request->user())
            : $tracking->disableTracking($device, $request->user());

        $auditLogger->log('device.status_changed', $request->user(), $device, ['state' => $data]);

        return response()->json($device->load('latestLocation'));
    }

    public function status(Request $request, Device $device): JsonResponse
    {
        $this->authorizeDevice($request, $device);

        return response()->json([
            'device_id' => $device->id,
            'tracking_enabled' => $device->tracking_enabled,
            'is_lost' => $device->is_lost,
            'tracking_mode' => $device->tracking_mode,
            'should_send_location' => $device->shouldSendLocation(),
            'polling_interval_seconds' => $device->shouldSendLocation() ? 10 : 20,
            'heartbeat_interval_minutes' => 15,
        ]);
    }

    public function location(Request $request, Device $device, DeviceLocationService $locations): JsonResponse
    {
        $this->authorizeDevice($request, $device);

        if (! $device->shouldSendLocation()) {
            return response()->json(['message' => 'Location updates are disabled for this device.'], 422);
        }

        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'speed' => ['nullable', 'numeric', 'min:0'],
            'battery_level' => ['nullable', 'integer', 'between:0,100'],
            'tracking_mode' => ['nullable', Rule::in(['live'])],
            'is_inside_campus' => ['nullable', 'boolean'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        $location = $locations->store($device, [...$data, 'tracking_mode' => 'live']);

        return response()->json($location, 201);
    }

    private function authorizeDevice(Request $request, Device $device): void
    {
        abort_if(! $request->user()->hasPermission('manage-devices') && $device->user_id !== $request->user()->id, 403);
    }

    private function authorizeLinkedLostItem(Request $request, int|string|null $lostItemId): void
    {
        if (! $lostItemId) {
            return;
        }

        $lostItem = LostItem::query()->with('category')->findOrFail($lostItemId);

        abort_if($lostItem->user_id !== $request->user()->id && ! $request->user()->hasPermission('manage-devices'), 403);
        abort_if(! $lostItem->category->is_electronic, 422, 'GPS tracking can only be linked to electronic lost item reports.');
    }

    /**
     * @param  array<string, bool>  $data
     * @return array<string, mixed>
     */
    private function trackingState(array $data): array
    {
        if (($data['tracking_enabled'] ?? false) || ($data['is_lost'] ?? false)) {
            return [
                'tracking_enabled' => true,
                'is_lost' => true,
                'recovered_at' => null,
            ];
        }

        return [
            'tracking_enabled' => false,
            'is_lost' => false,
        ];
    }
}
