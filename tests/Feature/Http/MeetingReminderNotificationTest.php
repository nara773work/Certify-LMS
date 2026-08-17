<?php

namespace Tests\Feature\Http;

use App\Enums\MeetingStatus;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\MeetingReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MeetingReminderNotificationTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    public function test_day_before_reminder(): void
    {
        Notification::fake();

        $this->seed();

        $meeting = Meeting::factory()->create([
            'scheduled_at' => now()->addDay()->setTime(19, 0),
            'status' => MeetingStatus::Reserved,
        ]);

        $this->artisan(
            'notifications:send-meeting-reminders',
            ['--window' => 'eve']
        )->assertSuccessful();

        Notification::assertSentTo(
            $meeting->student,
            MeetingReminderNotification::class
        );
    }

    public function test_one_hour_before_reminder(): void
    {
        Notification::fake();

        $this->seed();

        $meeting = Meeting::factory()->create([
            'scheduled_at' => now()->addHour(),
            'status' => MeetingStatus::Reserved,
        ]);

        $this->artisan(
            'notifications:send-meeting-reminders',
            ['--window' => 'one_hour_before']
        )->assertSuccessful();

        Notification::assertSentTo(
            $meeting->student,
            MeetingReminderNotification::class
        );
    }

    public function test_not_send_notification(): void
    {
        Notification::fake();

        $this->seed();

        $meeting = Meeting::factory()->create([
            'scheduled_at' => now()->addDays(3),
            'status' => MeetingStatus::Reserved,
        ]);

        $this->artisan(
            'notifications:send-meeting-reminders',
            ['--window' => 'eve']
        )->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_canceled_meeting(): void
    {
        Notification::fake();

        $this->seed();

        $meeting = Meeting::factory()->create([
            'scheduled_at' => now()->addDay()->setTime(19, 0),
            'status' => MeetingStatus::Canceled,
        ]);

        $this->artisan(
            'notifications:send-meeting-reminders',
            ['--window' => 'eve']
        )->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_same_reminder_twice(): void
    {
        Notification::fake();

        $this->seed();

        $meeting = Meeting::factory()->create([
            'scheduled_at' => now()->addDay()->setTime(19, 0),
            'status' => MeetingStatus::Reserved,
        ]);

        // 1回目
        $this->artisan(
            'notifications:send-meeting-reminders',
            ['--window' => 'eve']
        )->assertSuccessful();

        // 2回目
        $this->artisan(
            'notifications:send-meeting-reminders',
            ['--window' => 'eve']
        )->assertSuccessful();

        Notification::assertSentToTimes(
            $meeting->student,
            MeetingReminderNotification::class,
            1
        );
    }
}
