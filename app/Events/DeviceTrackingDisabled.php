<?php

namespace App\Events;

use App\Models\Device;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceTrackingDisabled implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public Device $device) {}

    public function broadcastOn(): Channel
    {
        return new Channel('devices.'.$this->device->id);
    }
}
