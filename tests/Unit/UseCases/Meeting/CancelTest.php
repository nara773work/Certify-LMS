<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Meeting;

use App\Enums\MeetingQuotaTransactionType;
use App\Enums\MeetingStatus;
use App\Models\Certification;
use App\Models\CertificationCategory;
use App\Models\Enrollment;
use App\Models\Meeting;
use App\Models\MeetingQuotaTransaction;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\UseCases\Meeting\CancelAction;
use App\UseCases\MeetingQuota\RefundQuotaAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class CancelTest extends TestCase
{
    use RefreshDatabase;

    private function createTestData(
        ?string $googleCalendarEventId = null,
    ): array {
        $student = User::create([
            'name' => 'CancelTest受講生',
            'email' => 'student-cancel-test@example.com',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);

        $coach = User::create([
            'name' => 'CancelTestコーチ',
            'email' => 'coach-cancel-test@example.com',
            'password' => bcrypt('password'),
            'role' => 'coach',
        ]);

        $category = CertificationCategory::create([
            'name' => 'CancelTestカテゴリ',
            'slug' => 'cancel-test-category',
        ]);

        $certification = Certification::create([
            'category_id' => $category->id,
            'name' => 'CancelTest資格',
            'description' => 'CancelActionテスト用資格',
            'difficulty' => 'beginner',
            'status' => 'published',
            'created_by_user_id' => $coach->id,
            'updated_by_user_id' => $coach->id,
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'certification_id' => $certification->id,
        ]);

        $meeting = Meeting::create([
            'enrollment_id' => $enrollment->id,
            'coach_id' => $coach->id,
            'student_id' => $student->id,
            'scheduled_at' => Carbon::tomorrow()->setTime(19, 0),
            'status' => MeetingStatus::Reserved->value,
            'topic' => 'CancelActionテスト面談',
            'meeting_url_snapshot' => 'https://example.com/meeting',
            'google_calendar_event_id' => $googleCalendarEventId,
        ]);

        // キャンセル前に消費済みトランザクションを作る
        $consumed = MeetingQuotaTransaction::create([
            'user_id' => $student->id,
            'type' => MeetingQuotaTransactionType::Consumed,
            'amount' => -1,
            'related_meeting_id' => $meeting->id,
            'occurred_at' => now(),
        ]);

        $meeting->update([
            'meeting_quota_transaction_id' => $consumed->id,
        ]);

        return compact(
            'student',
            'coach',
            'category',
            'certification',
            'enrollment',
            'meeting',
            'consumed',
        );
    }

    private function createAction(
        bool $googleConnected = false,
    ): CancelAction {
        $googleCalendarService = Mockery::mock(
            GoogleCalendarService::class,
        );

        if ($googleConnected) {
            $googleCalendarService
                ->shouldReceive('deleteEvent')
                ->once();
        } else {
            $googleCalendarService
                ->shouldNotReceive('deleteEvent');
        }

        return new CancelAction(
            app(RefundQuotaAction::class),
            $googleCalendarService,
        );
    }

    public function test_面談をキャンセルできる(): void
    {
        $data = $this->createTestData();

        $action = $this->createAction();

        ($action)(
            $data['meeting'],
            $data['student'],
        );

        $meeting = $data['meeting']->fresh();

        $this->assertSame(
            MeetingStatus::Canceled,
            $meeting->status,
        );

        $this->assertSame(
            $data['student']->id,
            $meeting->canceled_by_user_id,
        );

        $this->assertNotNull($meeting->canceled_at);
    }

    public function test_キャンセルすると面談回数が返却される(): void
    {
        $data = $this->createTestData();

        $action = $this->createAction();

        ($action)(
            $data['meeting'],
            $data['student'],
        );

        $refund = MeetingQuotaTransaction::query()
            ->where('user_id', $data['student']->id)
            ->where(
                'type',
                MeetingQuotaTransactionType::Refunded->value,
            )
            ->where(
                'related_meeting_id',
                $data['meeting']->id,
            )
            ->first();

        $this->assertNotNull($refund);

        $this->assertSame(1, $refund->amount);
    }

    public function test_google_calendar連携済みならイベントを削除する(): void
    {
        $eventId = 'google-event-cancel-test';

        $data = $this->createTestData($eventId);

        $action = $this->createAction(
            googleConnected: true,
        );

        ($action)(
            $data['meeting'],
            $data['student'],
        );

        $meeting = $data['meeting']->fresh();

        $this->assertSame(
            MeetingStatus::Canceled,
            $meeting->status,
        );

        $this->assertSame(
            $eventId,
            $meeting->google_calendar_event_id,
        );
    }
}
