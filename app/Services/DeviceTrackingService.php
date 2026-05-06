<?php

namespace App\Services;

use App\Events\DeviceTrackingDisabled;
use App\Events\DeviceTrackingEnabled;
use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeviceTrackingService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly FirebaseNotificationService $firebase,
    ) {}

    public function enableActiveSearch(Device $device, User $actor): Device
    {
        return DB::transaction(function () use ($device, $actor): Device {
            $before = $device->only(['tracking_enabled', 'is_lost', 'tracking_mode', 'active_search_started_at']);

            $device->update([
                'tracking_enabled' => true,
                'is_lost' => true,
                'tracking_mode' => 'live',
                'active_search_started_at' => now(),
                'active_search_ended_at' => null,
                'recovered_at' => null,
            ]);

            $this->auditLogger->log('device.active_search_enabled', $actor, $device, [
                'before' => $before,
                'after' => $device->fresh()->only(['tracking_enabled', 'is_lost', 'tracking_mode', 'active_search_started_at']),
            ]);

            DeviceTrackingEnabled::dispatch($device->fresh());
            $this->firebase->sendTrackingCommand($device->fresh(), 'START_TRACKING');

            return $device->fresh();
        });
    }

    public function disableTracking(Device $device, User $actor): Device
    {
        return DB::transaction(function () use ($device, $actor): Device {
            $before = $device->only(['tracking_enabled', 'is_lost', 'tracking_mode', 'active_search_ended_at']);

            $device->update([
                'tracking_enabled' => false,
                'is_lost' => false,
                'tracking_mode' => 'heartbeat',
                'active_search_ended_at' => now(),
            ]);

            $this->auditLogger->log('device.tracking_disabled', $actor, $device, [
                'before' => $before,
                'after' => $device->fresh()->only(['tracking_enabled', 'is_lost', 'tracking_mode', 'active_search_ended_at']),
            ]);

            DeviceTrackingDisabled::dispatch($device->fresh());
            $this->firebase->sendTrackingCommand($device->fresh(), 'STOP_TRACKING');

            return $device->fresh();
        });
    }

    public function markRecovered(Device $device, User $actor): Device
    {
        return DB::transaction(function () use ($device, $actor): Device {
            $device = $this->disableTracking($device, $actor);

            $device->update([
                'tracking_mode' => 'idle',
                'recovered_at' => now(),
            ]);

            $device->lostItem?->update(['status' => 'recovered']);

            $this->auditLogger->log('device.recovered', $actor, $device, [
                'device_id' => $device->id,
                'lost_item_id' => $device->lost_item_id,
            ]);

            return $device->fresh();
        });
    }
}
