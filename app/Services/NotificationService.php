<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function send(User $user, string $type, string $title, string $message, array $data = []): Notification
    {
        $notification = $this->sendInAppNotification(
            title: $title,
            message: $message,
            category: $data['category'] ?? $type,
            type: $type,
            createdBy: null,
            recipients: collect([$user]),
            entityType: $data['entity_type'] ?? null,
            entityId: $data['entity_id'] ?? null,
            actionUrl: $data['action_url'] ?? null,
            data: $data,
            level: $data['level'] ?? 'info'
        )->first();

        Mail::raw($message, function ($mail) use ($user, $title): void {
            $mail->to($user->email)->subject($title);
        });

        $notification?->forceFill(['emailed_at' => now()])->save();

        return $notification;
    }

    /**
     * @param  iterable<User>  $recipients
     * @param  array<string, mixed>  $data
     * @return Collection<int, Notification>
     */
    public function sendInAppNotification(
        string $title,
        string $message,
        string $category,
        string $type,
        ?User $createdBy,
        iterable $recipients,
        ?string $entityType = null,
        int|string|null $entityId = null,
        ?string $actionUrl = null,
        array $data = [],
        ?string $level = null
    ): Collection {
        return collect($recipients)
            ->filter(fn (User $user) => $user->exists)
            ->unique('id')
            ->map(fn (User $recipient) => Notification::query()->create([
                'user_id' => $recipient->id,
                'type' => $type,
                'category' => $category,
                'level' => $level ?? $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'action_url' => $actionUrl,
                'created_by' => $createdBy?->id,
            ]))
            ->values();
    }

    /**
     * @param  array<int, string>  $permissions
     * @return EloquentCollection<int, User>
     */
    public function recipientsForPermissions(array $permissions): EloquentCollection
    {
        return User::query()
            ->whereHas('roles.permissions', fn ($query) => $query->whereIn('name', $permissions))
            ->with('roles.permissions')
            ->get();
    }

    /**
     * @param  array<int, string>  $permissions
     * @param  array<string, mixed>  $data
     * @return Collection<int, Notification>
     */
    public function notifyPermissionHolders(
        array $permissions,
        string $title,
        string $message,
        string $category,
        string $type,
        User $createdBy,
        string $entityType,
        int|string $entityId,
        string $actionUrl,
        array $data = []
    ): Collection {
        return $this->sendInAppNotification(
            title: $title,
            message: $message,
            category: $category,
            type: $type,
            createdBy: $createdBy,
            recipients: $this->recipientsForPermissions($permissions),
            entityType: $entityType,
            entityId: $entityId,
            actionUrl: $actionUrl,
            data: ['required_permissions' => $permissions, ...$data]
        );
    }
}
