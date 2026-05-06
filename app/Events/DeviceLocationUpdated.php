<?php

namespace App\Events;

use App\Models\DeviceLocation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public DeviceLocation $location) {}

    public function broadcastOn(): Channel
    {
        return new Channel('devices.'.$this->location->device_id);
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'device_id' => $this->location->device_id,
            'latitude' => $this->location->latitude,
            'longitude' => $this->location->longitude,
            'accuracy' => $this->location->accuracy,
            'battery_level' => $this->location->battery_level,
            'tracking_mode' => $this->location->tracking_mode,
            'recorded_at' => $this->location->recorded_at?->toIso8601String(),
        ];
    }
}
