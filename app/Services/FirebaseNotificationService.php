<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    public function sendTrackingCommand(Device $device, string $command): void
    {
        $serverKey = config('services.firebase.server_key');

        if (! $serverKey || blank($device->fcm_token)) {
            return;
        }

        try {
            Http::withHeaders(['Authorization' => "key={$serverKey}"])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $device->fcm_token,
                'priority' => 'high',
                'content_available' => true,
                'data' => [
                    'command' => $command,
                    'device_uuid' => $device->device_uuid,
                ],
            ])->throw();
        } catch (\Throwable $exception) {
            Log::warning('Firebase tracking command failed.', [
                'device_id' => $device->id,
                'command' => $command,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
