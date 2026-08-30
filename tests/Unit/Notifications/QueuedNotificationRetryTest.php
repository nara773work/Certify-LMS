<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Notifications\AnnouncementNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueuedNotificationRetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_通知には3回のリトライ設定がある(): void
    {
        $notification = (new \ReflectionClass(
            AnnouncementNotification::class
        ))->newInstanceWithoutConstructor();

        $this->assertSame(3, $notification->tries);
        $this->assertSame([10, 30, 60], $notification->backoff);
    }

    public function test_通知がキューに投入される(): void
    {
        Queue::fake();

        $notification = (new \ReflectionClass(
            AnnouncementNotification::class
        ))->newInstanceWithoutConstructor();

        Queue::push($notification);

        Queue::assertPushed(AnnouncementNotification::class);
    }
}