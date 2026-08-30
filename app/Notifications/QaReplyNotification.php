<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\QaReply;

class QaReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    
    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public QaReply $qaReply,
    ) {
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'QaBoardReply',
            'title' => '質問掲示板',
            'message' => '質問に回答が届きました',
            'url' => route('qa-board.show', $this->qaReply->thread->id),
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
