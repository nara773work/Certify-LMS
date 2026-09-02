<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiNotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未ログインユーザーは通知APIを利用できない
     */
    public function test_guest_cannot_view_notifications(): void
    {
        $this->seed();

        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(401);
    }

    /**
     * ログインユーザーは通知一覧を取得できる
     */
    public function test_user_can_view_notifications(): void
    {
        $this->seed();

        $user = User::where(
            'email',
            'student@certify-lms.test'
        )->firstOrFail();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'notifications',
            'unread_count',
        ]);
    }

    /**
     * 自分の通知だけ取得できる
     */
    public function test_user_can_only_view_own_notifications(): void
    {
        $this->seed();

        $student = User::where(
            'email',
            'student@certify-lms.test'
        )->firstOrFail();

        $coach = User::where(
            'email',
            'coach@certify-lms.test'
        )->firstOrFail();

        $response = $this->actingAs($student)
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200);

        $notifications = $response->json('notifications');

        foreach ($notifications as $notification) {
            $this->assertSame(
                $student->id,
                $notification['notifiable_id']
            );
        }

        $response = $this->actingAs($coach)
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200);

        $notifications = $response->json('notifications');

        foreach ($notifications as $notification) {
            $this->assertSame(
                $coach->id,
                $notification['notifiable_id']
            );
        }
    }

    /**
     * 未読件数が取得できる
     */
    public function test_unread_count_is_returned(): void
    {
        $this->seed();

        $user = User::where(
            'email',
            'student@certify-lms.test'
        )->firstOrFail();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'unread_count',
        ]);

        $this->assertIsInt(
            $response->json('unread_count')
        );
    }

    /**
     * 自分の通知を既読にできる
     */
    public function test_user_can_mark_own_notification_as_read(): void
    {
        $this->seed();

        $user = User::where(
            'email',
            'student@certify-lms.test'
        )->firstOrFail();

        $notification = $user->notifications()
            ->whereNull('read_at')
            ->firstOrFail();

        $response = $this->actingAs($user)
            ->postJson(
                "/api/v1/notifications/{$notification->id}/read"
            );

        $response->assertStatus(200);

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
            'read_at' => null,
        ]);
    }

    /**
     * 他ユーザーの通知を既読にできない
     */
    public function test_user_cannot_mark_other_users_notification_as_read(): void
    {
        $this->seed();

        $student = User::where(
            'email',
            'student@certify-lms.test'
        )->firstOrFail();

        $coach = User::where(
            'email',
            'coach@certify-lms.test'
        )->firstOrFail();

        $notification = $coach->notifications()
            ->whereNull('read_at')
            ->firstOrFail();

        $response = $this->actingAs($student)
            ->postJson(
                "/api/v1/notifications/{$notification->id}/read"
            );

        $response->assertStatus(404);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'read_at' => null,
        ]);
    }

    /**
     * 全件既読にできる
     */
    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $this->seed();

        $user = User::where(
            'email',
            'student@certify-lms.test'
        )->firstOrFail();

        $this->assertGreaterThan(
            0,
            $user->notifications()
                ->whereNull('read_at')
                ->count()
        );

        $response = $this->actingAs($user)
            ->postJson('/api/v1/notifications/read-all');

        $response->assertStatus(200);

        $this->assertSame(
            0,
            $user->notifications()
                ->whereNull('read_at')
                ->count()
        );
    }

    /**
     * 他ユーザーの通知には影響しない
     */
    public function test_read_all_does_not_affect_other_users(): void
    {
        $this->seed();

        $student = User::where(
            'email',
            'student@certify-lms.test'
        )->firstOrFail();

        $coach = User::where(
            'email',
            'coach@certify-lms.test'
        )->firstOrFail();

        $coachUnreadBefore = $coach->notifications()
            ->whereNull('read_at')
            ->count();

        $this->actingAs($student)
            ->postJson('/api/v1/notifications/read-all')
            ->assertStatus(200);

        $coachUnreadAfter = $coach->notifications()
            ->whereNull('read_at')
            ->count();

        $this->assertSame(
            $coachUnreadBefore,
            $coachUnreadAfter
        );
    }

    /**
     * 通知一覧画面を表示できる
     */
    public function test_user_can_view_notification_page(): void
    {
        $this->seed();

        $user = User::where(
            'email',
            'student@certify-lms.test'
        )->firstOrFail();

        $response = $this->actingAs($user)
            ->get('/notifications');

        $response->assertStatus(200);
    }

    /**
     * 自分の通知詳細を表示できる
     */
    public function test_user_can_view_own_notification_detail(): void
    {
        $this->seed();

        $user = User::where(
            'email',
            'student@certify-lms.test'
        )->firstOrFail();

        $notification = $user->notifications()
            ->firstOrFail();

        $response = $this->actingAs($user)
            ->get(
                "/notifications/{$notification->id}"
            );

        $response->assertStatus(200);
    }

    /**
     * 他ユーザーの通知詳細を表示できない
     */
    public function test_user_cannot_view_other_users_notification_detail(): void
    {
        $this->seed();

        $student = User::where(
            'email',
            'student@certify-lms.test'
        )->firstOrFail();

        $coach = User::where(
            'email',
            'coach@certify-lms.test'
        )->firstOrFail();

        $notification = $coach->notifications()
            ->firstOrFail();

        $response = $this->actingAs($student)
            ->get(
                "/notifications/{$notification->id}"
            );

        $response->assertStatus(404);
    }
}
