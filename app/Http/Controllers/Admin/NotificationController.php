<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.notifications', [
            'notifications' => $request->user()->appNotifications()->with('creator')->latest()->paginate(25),
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->appNotifications()
            ->with('creator')
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn (Notification $notification) => $this->serialize($notification));

        return response()->json([
            'unread_count' => $request->user()->appNotifications()->whereNull('read_at')->count(),
            'new' => $notifications->whereNull('read_at')->values(),
            'older' => $notifications->whereNotNull('read_at')->values(),
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => $request->user()->appNotifications()->whereNull('read_at')->count(),
        ]);
    }

    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        abort_if($notification->user_id !== $request->user()->id, 403);

        $notification->update(['read_at' => now()]);

        return response()->json($this->serialize($notification->fresh('creator')));
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->appNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json([
            'unread_count' => 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Notification $notification): array
    {
        $initials = str($notification->creator?->name ?? 'System')
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part) => str($part)->substr(0, 1)->upper()->toString())
            ->join('');

        return [
            'id' => $notification->id,
            'title' => $notification->title,
            'message' => $notification->message,
            'category' => $notification->category,
            'type' => $notification->type,
            'level' => $notification->level,
            'entity_type' => $notification->entity_type,
            'entity_id' => $notification->entity_id,
            'action_url' => $notification->action_url,
            'created_by' => $notification->creator?->name,
            'creator_initials' => $initials ?: 'SY',
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at->toIso8601String(),
            'time_ago' => $notification->created_at->diffForHumans(),
        ];
    }
}
