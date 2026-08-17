<?php

namespace App\Console\Commands;

use App\Enums\MeetingStatus;
use App\Models\Meeting;
use App\Notifications\MeetingReminderNotification;
use Illuminate\Console\Command;

class MeetingRemindersCommand extends Command
{
    protected $signature = 'notifications:send-meeting-reminders
                            {--window= : eve または one-hour}';

    protected $description = '面談のリマインダー通知を送信する';

    public function handle(): int
    {
        $window = $this->option('window');

        if (!in_array($window, ['eve', 'one-hour'], true)) {
            $this->error('--window=eve または --window=one-hour を指定してください。');

            return self::FAILURE;
        }

        if ($window === 'eve') {
            $this->sendDayBeforeReminders();
        }

        if ($window === 'one-hour') {
            $this->sendOneHourBeforeReminders();
        }

        return self::SUCCESS;
    }

    private function sendDayBeforeReminders(): void
    {
        $meetings = Meeting::query()
            ->where('status', MeetingStatus::Reserved->value)
            ->whereBetween('scheduled_at', [
                now()->addDay()->startOfMinute(),
                now()->addDay()->endOfMinute(),
            ])
            ->get();

        foreach ($meetings as $meeting) {
            $this->sendReminder($meeting, 'day_before');
        }
    }

    private function sendOneHourBeforeReminders(): void
    {
        $meetings = Meeting::query()
            ->where('status', MeetingStatus::Reserved->value)
            ->whereBetween('scheduled_at', [
                now()->addHour()->startOfMinute(),
                now()->addHour()->endOfMinute(),
            ])
            ->get();

        foreach ($meetings as $meeting) {
            $this->sendReminder($meeting, 'one_hour_before');
        }
    }

    private function sendReminder(Meeting $meeting, string $timing): void
    {
        $alreadySent = $meeting->student
            ->notifications()
            ->where('type', MeetingReminderNotification::class)
            ->where('data->meeting_id', $meeting->id)
            ->where('data->timing', $timing)
            ->exists();

        if ($alreadySent) {
            return;
        }

        $meeting->student->notify(
            new MeetingReminderNotification(
                $meeting,
                $timing
            )
        );
    }
}