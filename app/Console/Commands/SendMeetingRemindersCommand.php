<?php

namespace App\Console\Commands;

use App\Enums\MeetingStatus;
use App\Models\Meeting;
use App\Notifications\MeetingReminderNotification;
use Illuminate\Console\Command;

class SendMeetingRemindersCommand extends Command
{
    protected $signature = 'notifications:send-meeting-reminders
                            {--window= : eve または one_hour_before}';

    protected $description = '面談のリマインダー通知を送信する';

    public function handle(): int
    {
        $this->info('リマインダー処理開始');

        $window = $this->option('window');

        $this->info('window: ' . $window);

        if (!in_array($window, ['eve', 'one_hour_before'], true)) {
            $this->error(
                '--window=eve または --window=one_hour_before を指定してください。'
            );

            return self::FAILURE;
        }

        if ($window === 'eve') {
            $this->sendDayBeforeReminders();
        }

        if ($window === 'one_hour_before') {
            $this->sendOneHourBeforeReminders();
        }


        return self::SUCCESS;
    }

    private function sendDayBeforeReminders(): void
{
        $tomorrow = now()->addDay();

        $meetings = Meeting::query()
            ->where('status', MeetingStatus::Reserved->value)
            ->whereDate('scheduled_at', $tomorrow->toDateString())
            ->get();

        $this->info('対象件数: ' . $meetings->count());

        foreach ($meetings as $meeting) {
            $this->sendReminder($meeting, 'day_before');
        }
}

    private function sendOneHourBeforeReminders(): void
{   
        $targetStart = now()->addHour()->startOfHour();
        $targetEnd = now()->addHour()->endOfHour();

        $meetings = Meeting::query()
            ->where('status', MeetingStatus::Reserved->value)
            ->whereBetween('scheduled_at', [
                $targetStart,
                $targetEnd,
            ])
            ->get();

        $this->info('対象件数: ' . $meetings->count());

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
            $this->info('すでに送信済み: ' . $meeting->id);
            return;
        }

        $meeting->student->notify(
            new MeetingReminderNotification(
                $meeting,
                $timing
            )
        );

        $this->info(
            "通知送信完了: meeting={$meeting->id}, timing={$timing}"
        );
    }
}