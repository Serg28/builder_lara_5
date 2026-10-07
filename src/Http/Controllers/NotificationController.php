<?php

namespace Vis\Builder;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->input('limit', 5);

        $adminUser = app('user') ?: null;
        if (!$adminUser) {
            return response()->json(['notifications' => [], 'unread' => 0]);
        }

        $notifications = $adminUser->notifications()
            ->orderByRaw('read_at IS NULL DESC') // Непрочитані (read_at is null) будуть першими
            ->orderBy('created_at', 'desc')      // Сортувати latest всередині груп
            ->paginate($limit);

        $data = $notifications->map(function ($notification) {
            return [
                'id' => $notification->id,
                'data' => $notification->data,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
                'created_human' => Carbon::parse($notification->created_at)->diffForHumans(),
            ];
        });

        return response()->json([
            'notifications' => $data,
            'unread' => $adminUser->unreadNotifications()->count(),
            'next_page_url' => $notifications->nextPageUrl(),
        ]);
    }

    public function markRead(Request $request)
    {
        $adminUser = app('user') ?: null;
        $notification = $adminUser->notifications()->findOrFail($request->id);

        if ($request->read) {
            $notification->markAsRead();
        } else {
            $notification->update(['read_at' => null]);
        }

        return response()->json(['message' => 'Статус оновлено']);
    }

    public function markAllRead(Request $request)
    {
        $adminUser = app('user') ?: null;

        if ($adminUser) {
            $adminUser->unreadNotifications->markAsRead();
        }

        return response()->json(['message' => 'Усі сповіщення прочитані']);
    }
}
