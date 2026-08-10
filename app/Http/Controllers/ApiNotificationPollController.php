<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiNotificationPollController extends Controller
{
    public function poll(Request $request): JsonResponse
    {
        $lastId = $request->input('last_id', 0);

        $notifications = Notification::query()
            ->where('id', '>', $lastId)
            ->where('is_read', false)
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        if ($notifications->isEmpty()) {
            return response()->json([
                'title' => 'No new notifications',
                'body' => '',
                'notifications' => [],
            ]);
        }

        return response()->json([
            'title' => $notifications->first()->title ?? 'Notification',
            'body' => $notifications->first()->body ?? '',
            'notifications' => $notifications->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'body' => $n->body,
                'created_at' => $n->created_at->toISOString(),
            ]),
        ]);
    }
}
