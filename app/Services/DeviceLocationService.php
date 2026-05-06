<?php

namespace App\Services;

use App\Events\DeviceLocationUpdated;
use App\Models\Device;
use App\Models\DeviceLocation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeviceLocationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function store(Device $device, array $data): DeviceLocation
    {
        return DB::transaction(function () use ($device, $data): DeviceLocation {
            $recordedAt = $data['recorded_at'] ?? now();

            $location = $device->locations()->create([
                ...$data,
                'tracking_mode' => $data['tracking_mode'] ?? 'heartbeat',
                'recorded_at' => $recordedAt,
            ]);

            $device->update([
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'last_latitude' => $data['latitude'],
                'last_longitude' => $data['longitude'],
                'last_accuracy' => $data['accuracy'] ?? null,
                'last_battery_level' => $data['battery_level'] ?? null,
                'last_seen_at' => $recordedAt,
            ]);

            $this->cacheLatestLocation($device->fresh(), $location);
            DeviceLocationUpdated::dispatch($location);

            return $location;
        });
    }

    private function cacheLatestLocation(Device $device, DeviceLocation $location): void
    {
        try {
            Cache::put("devices:{$device->id}:latest-location", [
                'device_id' => $device->id,
                'device_uuid' => $device->device_uuid,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'accuracy' => $location->accuracy,
                'battery_level' => $location->battery_level,
                'tracking_mode' => $location->tracking_mode,
                'recorded_at' => $location->recorded_at?->toIso8601String(),
            ], now()->addMinutes(30));
        } catch (\Throwable $exception) {
            Log::warning('Unable to cache latest device location.', [
                'device_id' => $device->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
