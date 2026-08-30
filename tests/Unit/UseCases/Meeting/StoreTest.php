<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Meeting;

use App\Enums\MeetingStatus;
use App\Models\Certification;
use App\Models\CertificationCoachAssignment;
use App\Models\CoachAvailability;
use App\Models\Enrollment;
use App\Models\Meeting;
use App\Models\MeetingQuotaTransaction;
use App\Models\User;
use App\Services\CoachMeetingLoadService;
use App\Services\GoogleCalendarService;
use App\Services\MeetingAvailabilityService;
use App\Services\MeetingQuotaService;
use App\UseCases\Meeting\StoreAction;
use App\UseCases\MeetingQuota\ConsumeQuotaAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(
        string $name,
        string $email,
        string $role,
        int $maxMeetings = 0,
    ): User {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => $role,
            'status' => 'in_progress',
            'profile_setup_completed' => true,
            'email_verified_at' => now(),
            'max_meetings' => $maxMeetings,
        ]);
    }

    private function createTestData(): array
    {
        $admin = $this->createUser(
            'テスト管理者',
            'admin-store-test@example.com',
            'admin',
        );

        // max_meetings = 1 にすることで、面談を1回予約できる状態にする
        $student = $this->createUser(
            'テスト受講生',
            'student-store-test@example.com',
            'student',
            1,
        );

        $coach = $this->createUser(
            'テストコーチ',
            'coach-store-test@example.com',
            'coach',
        );

        $category = \App\Models\CertificationCategory::create([
            'name' => 'StoreTestカテゴリ',
            'slug' => 'store-test-category',
        ]);

        $certification = Certification::create([
            'category_id' => $category->id,
            'name' => 'StoreTest資格',
            'description' => 'StoreActionテスト用資格',
            'difficulty' => 'beginner',
            'status' => 'published',
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'certification_id' => $certification->id,
        ]);

        CertificationCoachAssignment::create([
            'certification_id' => $certification->id,
            'user_id' => $coach->id,
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'unassigned_at' => null,
        ]);

        return [
            'admin' => $admin,
            'student' => $student,
            'coach' => $coach,
            'category' => $category,
            'certification' => $certification,
            'enrollment' => $enrollment,
        ];
    }

    private function createAvailability(
        User $coach,
        Carbon $scheduledAt,
    ): CoachAvailability {
        return CoachAvailability::create([
            'coach_id' => $coach->id,
            'day_of_week' => $scheduledAt->dayOfWeek,
            'start_time' => $scheduledAt->format('H:i:s'),
            'end_time' => $scheduledAt->copy()->addHour()->format('H:i:s'),
            'is_active' => true,
        ]);
    }

    private function createAction(
        bool $googleConnected = false,
        ?string $googleEventId = null,
    ): StoreAction {
        $googleCalendarService = Mockery::mock(
            GoogleCalendarService::class
        );

        $googleCalendarService
            ->shouldReceive('isConnected')
            ->andReturn($googleConnected);

        if ($googleConnected) {
            $googleCalendarService
                ->shouldReceive('createEvent')
                ->once()
                ->andReturn($googleEventId);
        }

        return new StoreAction(
            app(MeetingAvailabilityService::class),
            app(MeetingQuotaService::class),
            app(ConsumeQuotaAction::class),
            $googleCalendarService,
            app(CoachMeetingLoadService::class),
        );
    }

    /**
     * 面談を予約できる。
     */
    public function test_面談を予約できる(): void
    {
        Notification::fake();

        $data = $this->createTestData();

        $scheduledAt = Carbon::tomorrow()
            ->setTime(19, 0);

        $this->createAvailability(
            $data['coach'],
            $scheduledAt,
        );

        $action = $this->createAction();

        $meeting = $action(
            $data['enrollment'],
            $data['student'],
            [
                'scheduled_at' => $scheduledAt->toIso8601String(),
                'topic' => 'テスト面談',
            ],
        );

        $this->assertInstanceOf(
            Meeting::class,
            $meeting,
        );

        $this->assertSame(
            $data['coach']->id,
            $meeting->coach_id,
        );

        $this->assertSame(
            $data['student']->id,
            $meeting->student_id,
        );

        $this->assertSame(
            $data['enrollment']->id,
            $meeting->enrollment_id,
        );

        $this->assertSame(
    MeetingStatus::Reserved,
    $meeting->status,
);

        $this->assertSame(
            'テスト面談',
            $meeting->topic,
        );

        $this->assertDatabaseHas('meetings', [
            'id' => $meeting->id,
            'coach_id' => $data['coach']->id,
            'student_id' => $data['student']->id,
            'enrollment_id' => $data['enrollment']->id,
            'status' => MeetingStatus::Reserved->value,
        ]);

        // 面談回数が1回消費されている
        $this->assertDatabaseHas('meeting_quota_transactions', [
            'user_id' => $data['student']->id,
            'related_meeting_id' => $meeting->id,
            'amount' => -1,
            'type' => 'consumed',
        ]);

        $this->assertSame(
            0,
            app(MeetingQuotaService::class)
                ->remaining($data['student']->fresh()),
        );
    }

    /**
     * Google Calendar連携済みならイベントを作成し、
     * event IDを面談へ保存する。
     */
    public function test_Google_Calendar連携済みならイベントを作成してイベントIDを保存する(): void
    {
        Notification::fake();

        $data = $this->createTestData();

        $scheduledAt = Carbon::tomorrow()
            ->setTime(19, 0);

        $this->createAvailability(
            $data['coach'],
            $scheduledAt,
        );

        $action = $this->createAction(
            true,
            'google-event-123',
        );

        $meeting = $action(
            $data['enrollment'],
            $data['student'],
            [
                'scheduled_at' => $scheduledAt->toIso8601String(),
                'topic' => 'Google Calendarテスト',
            ],
        );

        $this->assertSame(
            'google-event-123',
            $meeting->google_calendar_event_id,
        );

        $this->assertDatabaseHas('meetings', [
            'id' => $meeting->id,
            'google_calendar_event_id' => 'google-event-123',
        ]);

        $this->assertDatabaseHas('meeting_quota_transactions', [
            'user_id' => $data['student']->id,
            'related_meeting_id' => $meeting->id,
            'amount' => -1,
            'type' => 'consumed',
        ]);
    }

    /**
     * Google Calendar未連携でも面談予約できる。
     */
    public function test_Google_Calendar未連携でも面談予約できる(): void
    {
        Notification::fake();

        $data = $this->createTestData();

        $scheduledAt = Carbon::tomorrow()
            ->setTime(19, 0);

        $this->createAvailability(
            $data['coach'],
            $scheduledAt,
        );

        $action = $this->createAction();

        $meeting = $action(
            $data['enrollment'],
            $data['student'],
            [
                'scheduled_at' => $scheduledAt->toIso8601String(),
                'topic' => '未連携テスト',
            ],
        );

        $this->assertDatabaseHas('meetings', [
            'id' => $meeting->id,
            'coach_id' => $data['coach']->id,
            'student_id' => $data['student']->id,
            'status' => MeetingStatus::Reserved->value,
            'google_calendar_event_id' => null,
        ]);

        $this->assertDatabaseHas('meeting_quota_transactions', [
            'user_id' => $data['student']->id,
            'related_meeting_id' => $meeting->id,
            'amount' => -1,
            'type' => 'consumed',
        ]);
    }
}