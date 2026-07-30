<?php

namespace App\Http\Controllers;

use App\Events\NotificationSent;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->limit($request->input('limit', 50))
            ->get();

        $unreadCount = Notification::where('user_id', Auth::id())
            ->where('read', false)
            ->count();

        return response()->json([
            'notifications' => $notifications->map(fn ($n) => [
                'id' => $n->id,
                'icon' => $n->icon,
                'iconColor' => $n->icon_color,
                'title' => $n->title,
                'body' => $n->body,
                'url' => $n->url,
                'type' => $n->type,
                'read' => $n->read,
                'meta' => $n->meta,
                'time' => $n->created_at?->diffForHumans() ?? '',
                'created_at' => $n->created_at->toIso8601String(),
            ]),
            'unread_count' => $unreadCount,
        ]);
    }

    public function markRead(int $id): JsonResponse
    {
        $notification = Notification::where('user_id', Auth::id())->findOrFail($id);
        $notification->update(['read' => true]);

        return response()->json(['message' => 'Marked as read']);
    }

    public function markAllRead(): JsonResponse
    {
        Notification::where('user_id', Auth::id())
            ->where('read', false)
            ->update(['read' => true]);

        return response()->json(['message' => 'All marked as read']);
    }

    public function destroy(int $id): JsonResponse
    {
        Notification::where('user_id', Auth::id())->where('id', $id)->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function clearAll(): JsonResponse
    {
        Notification::where('user_id', Auth::id())->delete();

        return response()->json(['message' => 'All cleared']);
    }

    public static function notify(
        int $userId,
        string $title,
        ?string $body = null,
        string $icon = 'info',
        string $iconColor = 'text-primary',
        string $type = 'info',
        ?string $url = null,
        ?array $meta = null,
        bool $broadcast = true,
    ): Notification {
        $notification = Notification::create([
            'user_id' => $userId,
            'icon' => $icon,
            'icon_color' => $iconColor,
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'type' => $type,
            'meta' => $meta,
        ]);

        if ($broadcast) {
            NotificationSent::dispatch($userId, $notification);
        }

        return $notification;
    }
}
