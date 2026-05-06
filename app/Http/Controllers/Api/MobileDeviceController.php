<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\DeviceLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileDeviceController extends Controller
{
    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_uuid' => ['required', 'string', 'max:255'],
            'device_name' => ['required', 'string', 'max:255'],
            'device_type' => ['required', Rule::in(Device::TYPES)],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'os_name' => ['nullable', 'string', 'max:255'],
            'os_version' => ['nullable', 'string', 'max:255'],
            'app_version' => ['nullable', 'string', 'max:255'],
            'manual_imei' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'fcm_token' => ['nullable', 'string'],
            'location_permission_status' => ['nullable', 'string', 'max:50'],
        ]);

        $existing = Device::query()->where('device_uuid', $data['device_uuid'])->first();
        abort_if($existing && $existing->user_id !== $request->user()->id, 403);

        $device = Device::query()->updateOrCreate(
            ['device_uuid' => $data['device_uuid']],
            [
                'user_id' => $request->user()->id,
                'name' => $data['device_name'],
                'device_identifier' => $data['device_uuid'],
                'device_type' => $data['device_type'],
                'brand' => $data['brand'] ?? null,
                'model' => $data['model'] ?? null,
                'brand_model' => trim(($data['brand'] ?? '').' '.($data['model'] ?? '')) ?: null,
                'os_name' => $data['os_name'] ?? null,
                'os_version' => $data['os_version'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'manual_imei' => $data['manual_imei'] ?? null,
                'serial_number' => $data['serial_number'] ?? null,
                'serial_imei' => $data['manual_imei'] ?? $data['serial_number'] ?? null,
                'fcm_token' => $data['fcm_token'] ?? $existing?->fcm_token,
                'location_permission_status' => $data['location_permission_status'] ?? $existing?->location_permission_status ?? 'unknown',
                'tracking_mode' => $existing?->tracking_mode ?? 'idle',
            ]
        );

        return response()->json($device->fresh(), $existing ? 200 : 201);
    }

    public function status(Request $request, string $deviceUuid): JsonResponse
    {
        $device = $this->ownedDevice($request, $deviceUuid);

        return response()->json([
            'device_uuid' => $device->device_uuid,
            'is_lost' => $device->is_lost,
            'tracking_enabled' => $device->tracking_enabled,
            'tracking_mode' => $device->tracking_mode,
            'polling_interval_seconds' => $device->shouldSendLocation() ? 10 : 20,
            'heartbeat_interval_minutes' => 15,
            'location_permission_status' => $device->location_permission_status,
        ]);
    }

    public function location(Request $request, string $deviceUuid, DeviceLocationService $locations): JsonResponse
    {
        $device = $this->ownedDevice($request, $deviceUuid);

        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'speed' => ['nullable', 'numeric', 'min:0'],
            'battery_level' => ['nullable', 'integer', 'between:0,100'],
            'tracking_mode' => ['required', Rule::in(['heartbeat', 'live'])],
            'is_inside_campus' => ['nullable', 'boolean'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        if ($data['tracking_mode'] === 'live' && ! $device->shouldSendLocation()) {
            return response()->json(['message' => 'Live tracking is disabled for this device.'], 422);
        }

        if ($data['tracking_mode'] === 'heartbeat' && $device->location_permission_status === 'denied') {
            return response()->json(['message' => 'Location permission is denied for this device.'], 422);
        }

        return response()->json($locations->store($device, $data), 201);
    }

    public function fcmToken(Request $request, string $deviceUuid): JsonResponse
    {
        $device = $this->ownedDevice($request, $deviceUuid);
        $data = $request->validate(['fcm_token' => ['nullable', 'string']]);
        $device->update($data);

        return response()->json(['message' => 'FCM token updated.']);
    }

    public function permissionStatus(Request $request, string $deviceUuid): JsonResponse
    {
        $device = $this->ownedDevice($request, $deviceUuid);
        $data = $request->validate([
            'location_permission_status' => ['required', Rule::in(['unknown', 'granted', 'denied', 'foreground', 'background'])],
        ]);
        $device->update($data);

        return response()->json($device->fresh());
    }

    private function ownedDevice(Request $request, string $deviceUuid): Device
    {
        return Device::query()
            ->where('device_uuid', $deviceUuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
    }
}
