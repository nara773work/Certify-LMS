<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Announcement;
use App\Models\Certification;
use App\Enums\AnnouncementTargetType;
use App\Enums\CertificationStatus;
use App\Enums\UserRole;
use App\Notifications\AnnouncementNotification;

class AnnouncementController extends Controller
{
    public function index(){
        $announcements = Announcement::orderBy('created_at')->paginate(10);
        return view('announcement.management.index',compact('announcements'));
    }

    public function create(){

        $certifications = Certification::where('status',CertificationStatus::Published)->get();
        $students = User::all();
        return view('announcement.management.create',compact('certifications','students'));
    }

    public function store(Request $request){

        $announcement = Announcement::create([
            'title' => $request->title,
            'body'=> $request->body,
            'target_type'=> $request->target_type,
            'dispatched_at' => now(),
            'created_by' => auth()->id(),
        ]);

        $users = collect();

           // 配信対象を取得
        if ($request->target_type === AnnouncementTargetType::AllStudents->value) {

            // 全受講生
            $users = User::where('role', UserRole::Student)->get();

        } elseif ($request->target_type === AnnouncementTargetType::Certification->value) {

            // 資格指定
            $users = User::whereHas('enrollments', function ($query) use ($request) {
            $query->where('certification_id', $request->certification_id);
            })->get();

        } elseif ($request->target_type === AnnouncementTargetType::User->value) {

            // ユーザー指定
            $users = User::whereIn('id', $request->user_ids)->get();

        }

            $announcement->users()->sync($users->pluck('id'));

        foreach ($users as $user) {
            $user->notify(new AnnouncementNotification($announcement));

            $notification = $user->notifications()
                ->where('type', AnnouncementNotification::class)
                ->where('data->announcement_id', $announcement->id)
                ->latest()
                ->first();

            if ($notification) {
                $data = $notification->data;

                $data['url'] = route('notifications.show', [
                    'notification' => $notification->id,
                ]);

                $notification->update([
                    'data' => $data,
                ]);
            }
        }

        return redirect()->route('admin.announcements.index')->with('success','お知らせを配信しました');
    }

    public function show(Announcement $announcement){

        return view('announcement.management.show',compact('announcement'));
    }

    public function notificationshow(string $id)
{
    $notification = auth()->user()
        ->notifications()
        ->findOrFail($id);

    return view('notifications.show', compact('notification'));
}
}
