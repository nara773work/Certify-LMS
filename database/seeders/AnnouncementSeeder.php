<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Announcement;
use App\Models\User;
use App\Models\Certification;
use App\Enums\AnnouncementTargetType;
use App\Notifications\AnnouncementNotification;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        //前受講生
        $announcement = Announcement::create([
            'title'=>'全受講生の方へアクセス制限のお知らせ',
            'body'=>'システムメンテナンスのため、2026.09.10の13:00~15:00はアクセスが制限されます',
            'target_type' => AnnouncementTargetType::AllStudents->value,
            'dispatched_at' => now(),
            'created_by' => $admin->id,
        ]);

        $students = User::where('role', 'student')->get();

        $announcement->users()->sync($students->pluck('id'));

        foreach ($students as $student) {
            $student->notify(new AnnouncementNotification($announcement));
        }

        //資格指定
        $certification = Certification::first();

        $announcement = Announcement::create([
            'title' => '基本情報受講の方へお知らせ',
            'body' => '基本情報の講座が更新されました。',
            'target_type' => AnnouncementTargetType::Certification->value,
            'dispatched_at' => now(),
            'created_by' => $admin->id,
        ]);

        $cer_users = User::whereHas('enrollments', function ($query) use ($certification) {
            $query->where('certification_id', $certification->id);
        })->get();

        $announcement->users()->sync($cer_users->pluck('id'));

        foreach ($cer_users as $user) {
            $user->notify(new AnnouncementNotification($announcement));
        }

        //ユーザー指定
        $student = User::where('role', 'student')->first();

        $announcement = Announcement::create([
            'title' => '受講生花子さんへ不具合解消のお知らせ',
            'body' => '先日報告していただいた不具合を解消しましたのでお知らせいたします。ご不便をおかけいたしました。',
            'target_type' => AnnouncementTargetType::User->value,
            'dispatched_at' => now(),
            'created_by' => $admin->id,
        ]);

        $announcement->users()->sync([$student->id]);

        $student->notify(new AnnouncementNotification($announcement));
    }
}
