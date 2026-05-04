<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function send(User $user, string $type, string $title, string $message, array $data = []): Notification
    {
        $notification = Notification::query()->create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);

        Mail::raw($message, function ($mail) use ($user, $title): void {
            $mail->to($user->email)->subject($title);
        });

        $notification->forceFill(['emailed_at' => now()])->save();

        return $notification;
    }
}
