<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Notifications\AnnouncementNotification;
use App\Notifications\ChatMessageNotification;
use App\Notifications\MeetingReservationNotification;
use App\Notifications\QaReplyNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Tests\TestCase;

class QueuedNotificationTest extends TestCase
{
    /**
     * 通知がキュー処理対象になっていること。
     */
    public function test_通知4種類がキュー処理対象になっている(): void
    {
        $notifications = [
            AnnouncementNotification::class,
            ChatMessageNotification::class,
            MeetingReservationNotification::class,
            QaReplyNotification::class,
        ];

        foreach ($notifications as $notification) {
            $this->assertTrue(
                is_a($notification, ShouldQueue::class, true),
                "{$notification} が ShouldQueue を実装していません。"
            );
        }
    }

    /**
     * 通知にリトライ設定があること。
     */
    public function test_通知4種類にリトライ設定がある(): void
    {
        $notifications = [
            AnnouncementNotification::class,
            ChatMessageNotification::class,
            MeetingReservationNotification::class,
            QaReplyNotification::class,
        ];

        foreach ($notifications as $notification) {
            $instance = $this->createWithoutConstructor($notification);

            $this->assertSame(3, $instance->tries);
            $this->assertSame([10, 30, 60], $instance->backoff);
        }
    }

    /**
     * コンストラクタを実行せず通知インスタンスを生成する。
     *
     * FactoryやDBのデータを必要としない設定値のテスト用。
     */
    private function createWithoutConstructor(string $class): object
    {
        return (new \ReflectionClass($class))
            ->newInstanceWithoutConstructor();
    }
}