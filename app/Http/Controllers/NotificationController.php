<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Enums\NotificationStatus;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function index(Request $request){

        $user = Auth()->user();

        $unreadCount = $user->notifications()
            ->whereNull('read_at')
            ->count();

        $tab = $request->input('tab','all');

        $query = $user->notifications();

        if ($tab === 'unread') {
            $query->whereNull('read_at');
        }

        $notifications = $query
            ->paginate(10)
            ->withQueryString();


        return view('notifications.index',compact('unreadCount','tab','notifications'));
    }

    public function read(string $id)
{
        $notification = auth()->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        $data = $notification->data;

        if (!empty($data['url'])) {
            return redirect($data['url']);
        }

        return redirect()->route('notifications.show', [
            'notification' => $notification->id,
        ]);
}

        public function readall()
    {
        $notification = auth()->user()
            ->notifications()
            ->get()
            ->markAsRead();

        return back();
    }
}
