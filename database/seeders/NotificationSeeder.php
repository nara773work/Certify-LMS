<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\ChatMessage;
use App\Models\Meeting;
use App\Models\QaReply;
use App\Models\User;
use App\Notifications\ChatMessageNotification;
use App\Notifications\MeetingReservationNotification;
use App\Notifications\QaReplyNotification;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $student = User::where('role', UserRole::Student)->firstOrFail();
        $coach = User::where('role', UserRole::Coach)->firstOrFail();

        $chatMessage = ChatMessage::firstOrFail();
        $qaReply = QaReply::firstOrFail();
        $meeting = Meeting::firstOrFail();

        // 受講生花子

        $student->notify(
            new ChatMessageNotification($chatMessage)
        );

        $student->notify(
            new QaReplyNotification($qaReply)
        );

        $student->notify(
            new MeetingReservationNotification($meeting)
        );

        // チャット：既読
        $notification = $student->notifications()
            ->latest()
            ->first();

        if ($notification) {
            $notification->markAsRead();
        }

        // コーチ太郎

        $coach->notify(
            new MeetingReservationNotification($meeting)
        );

        $coach->notify(
            new ChatMessageNotification($chatMessage)
        );

        // 面談：既読
        $notification = $coach->notifications()
            ->latest()
            ->first();

        if ($notification) {
            $notification->markAsRead();
        }
    }
}
