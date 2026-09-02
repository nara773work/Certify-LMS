<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetingReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Meeting $meeting,
        public string $timing,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('面談リマインダー')
            ->line('予約されている面談のお知らせです。')
            ->line(
                '面談日時：'.
                $this->meeting->scheduled_at->format('Y年m月d日 H:i')
            )
            ->line('面談内容：'.$this->meeting->topic);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'notification_type' => 'meeting_reminder',
            'meeting_id' => $this->meeting->id,
            'timing' => $this->timing,
            'title' => '面談リマインダー',
            'body' => $this->timing === 'day_before'
                ? '明日は面談の予定があります。'
                : '1時間後に面談の予定があります。',
            'scheduled_at' => $this->meeting->scheduled_at->toDateTimeString(),
        ];
    }
}
