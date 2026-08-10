<?php

namespace Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use App\Enums\UserRole;
use App\Notifications\ChatMessageNotification;
use App\Notifications\QaReplyNotification;
use App\Notifications\MeetingReservationNotification;
use App\Models\ChatRoom;
use App\Models\QaThread;
use App\Models\Meeting;

class NotificationTest extends TestCase
{
    /**
     * A basic feature test example.
     */

    use RefreshDatabase;

    public function test_index(): void
    {
        $this->seed();

        $user = User::first();

        $response = $this->actingAs($user)
            ->get(route('notifications.index'));

        $response->assertStatus(200);
    }

    public function test_AsRead(): void
{
    $this->seed();

    $user = User::first();

    $notification = $user->notifications()->create([
        'id' => fake()->uuid(),
        'type' => 'chat',
        'data' => [
            'notification_type' => 'chat',
            'title' => 'チャットメッセージ',
            'message' => '新しいメッセージが届きました',
            'url' => route('chat.index'),
        ],
        'read_at' => null,
    ]);

    $response = $this->actingAs($user)
        ->post(route('notifications.markAsRead', $notification));

    $response->assertRedirect();

    $this->assertNotNull(
        $notification->fresh()->read_at
    );
}

public function test_AllAsRead(): void
{
    $this->seed();

    $user = User::first();

    $user->notifications()->createMany([
        [
            'id' => fake()->uuid(),
            'type' => 'chat',
            'data' => [
                'title' => 'チャット',
                'message' => '新しいメッセージ',
            ],
            'read_at' => null,
        ],
        [
            'id' => fake()->uuid(),
            'type' => 'qa',
            'data' => [
                'title' => '質問掲示板',
                'message' => '回答が届きました',
            ],
            'read_at' => null,
        ],
    ]);

    $response = $this->actingAs($user)
        ->post(route('notifications.markAllAsRead'));

    $response->assertRedirect();

    $this->assertEquals(
        0,
        $user->notifications()
            ->whereNull('read_at')
            ->count()
    );
}

public function test_meeting_send(): void
{
    $this->seed();

    Notification::fake();

    $student = User::where('role', UserRole::Student)
        ->firstOrFail();

    $enrollment = $student->enrollments()
        ->firstOrFail();

    $response = $this->actingAs($student)
        ->post(
            route('meetings.store', $enrollment),
            [
                'scheduled_at' => '2026-09-02 19:00',
                'topic' => '面談について',
            ]
        );

    $response->assertRedirect();

    $meeting = Meeting::latest()->firstOrFail();
    $coach = User::findOrFail($meeting->coach_id);

    Notification::assertSentTo(
        $coach,
        MeetingReservationNotification::class
    );
}

public function test_chat_send(): void
{
    $this->seed();

    Notification::fake();

    $sender = User::where('role', UserRole::Student)
        ->firstOrFail();

    $receiver = User::where('role', UserRole::Coach)
        ->firstOrFail();

    $room = ChatRoom::firstOrFail();

    $response = $this->actingAs($sender)
        ->post(
            route('chat.storeMessage', $room),
            [
                'body' => 'test',
            ]
        );

    $response->assertRedirect();

    Notification::assertSentTo(
        $receiver,
        ChatMessageNotification::class
    );
}

public function test_QaReply(): void
{
    $this->seed();

    Notification::fake();

    $student = User::where('role', UserRole::Student)
        ->firstOrFail();

    $coach = User::where('role', UserRole::Coach)
        ->firstOrFail();

    $thread = QaThread::where('user_id', $student->id)
        ->firstOrFail();

    $response = $this->actingAs($coach)
        ->post(
            route('qa-board.replies.store', $thread),
            [
                'body' => '回答しました。',
            ]
        );

    $response->assertRedirect();

    Notification::assertSentTo(
        $student,
        QaReplyNotification::class
    );
}

public function test_move(): void
{
    $this->seed();

    $user = User::firstOrFail();

    $notification = $user->notifications()->create([
        'id' => fake()->uuid(),
        'type' => 'App\Notifications\ChatMessageNotification',
        'data' => [
            'notification_type' => 'chat_message_received',
            'title' => 'チャット',
            'message' => '新しいメッセージ',
            'url' => route('chat.index'),
        ],
        'read_at' => null,
    ]);

    $response = $this->actingAs($user)
        ->post(route(
            'notifications.markAsRead',
            $notification
        ));

    $response->assertRedirect(
        route('chat.index')
    );
}
}
