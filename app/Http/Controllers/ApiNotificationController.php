<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class ApiNotificationController extends Controller
{
    /**
     * 自分の通知一覧を取得する。
     */
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->get();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $request->user()
                ->unreadNotifications()
                ->count(),
        ]);
    }

    /**
     * 自分の通知を1件既読にする。
     */
    public function read(Request $request, string $notification)
{
        $notificationModel = $request->user()
            ->notifications()
            ->where('id', $notification)
            ->firstOrFail();

        $notificationModel->markAsRead();

        return response()->json([
            'message' => '通知を既読にしました。',
        ]);
}

    /**
     * 自分の未読通知をすべて既読にする。
     */
    public function readAll(Request $request)
{
    $request->user()
        ->unreadNotifications
        ->markAsRead();

    return response()->json([
        'message' => 'すべての通知を既読にしました。',
    ]);
}
}