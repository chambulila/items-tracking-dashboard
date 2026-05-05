<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $devices = Device::query()
            ->with('latestLocation')
            ->when(! $request->user()->hasPermission('manage-devices'), fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest()
            ->paginate(20);

        return response()->json($devices);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'device_identifier' => ['required', 'string', 'max:255', 'unique:devices,device_identifier'],
            'brand_model' => ['nullable', 'string', 'max:255'],
            'serial_imei' => ['nullable', 'string', 'max:255'],
        ]);

        $device = $request->user()->devices()->create($data);

        return response()->json($device, 201);
    }

    public function show(Request $request, Device $device): JsonResponse
    {
        $this->authorizeDevice($request, $device);

        return response()->json($device->load(['latestLocation', 'locations' => fn ($query) => $query->latest('recorded_at')->limit(50)]));
    }

    public function updateStatus(Request $request, Device $device, AuditLogger $auditLogger): JsonResponse
    {
        $this->authorizeDevice($request, $device);

        $data = $request->validate([
            'tracking_enabled' => ['sometimes', 'boolean'],
            'is_lost' => ['sometimes', 'boolean'],
        ]);

        $before = $device->only(['tracking_enabled', 'is_lost']);

        if (array_key_exists('is_lost', $data) && $data['is_lost'] === false) {
            $data['tracking_enabled'] = false;
            $data['recovered_at'] = now();
        }

        $device->update($data);

        $auditLogger->log('device.status_changed', $request->user(), $device, ['before' => $before, 'after' => $device->only(['tracking_enabled', 'is_lost'])]);

        return response()->json($device->fresh('latestLocation'));
    }

    public function status(Request $request, Device $device): JsonResponse
    {
        $this->authorizeDevice($request, $device);

        return response()->json([
            'device_id' => $device->id,
            'tracking_enabled' => $device->tracking_enabled,
            'is_lost' => $device->is_lost,
            'should_send_location' => $device->shouldSendLocation(),
            'poll_after_seconds' => 20,
        ]);
    }

    public function location(Request $request, Device $device): JsonResponse
    {
        $this->authorizeDevice($request, $device);

        if (! $device->shouldSendLocation()) {
            return response()->json(['message' => 'Location updates are disabled for this device.'], 422);
        }

        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        $location = $device->locations()->create([
            ...$data,
            'recorded_at' => $data['recorded_at'] ?? now(),
        ]);

        return response()->json($location, 201);
    }

    private function authorizeDevice(Request $request, Device $device): void
    {
        abort_if(! $request->user()->hasPermission('manage-devices') && $device->user_id !== $request->user()->id, 403);
    }
}
