<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Meeting;

use App\Enums\MeetingStatus;
use App\Models\Certification;
use App\Models\CertificationCategory;
use App\Models\CertificationCoachAssignment;
use App\Models\Enrollment;
use App\Models\Meeting;
use App\Models\User;
use App\UseCases\Meeting\IndexAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テスト用ユーザーを直接作成する。
     */
    private function createUser(
        string $name,
        string $email,
        string $role,
    ): User {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => $role,
            'status' => 'in_progress',
            'profile_setup_completed' => true,
            'email_verified_at' => now(),
            'max_meetings' => 1,
        ]);
    }

    /**
     * 面談に必要な基本データを作成する。
     */
    private function createTestData(): array
    {
        $admin = $this->createUser(
            'テスト管理者',
            'admin-index-test@example.com',
            'admin',
        );

        $student = $this->createUser(
            'テスト受講生',
            'student-index-test@example.com',
            'student',
        );

        $otherStudent = $this->createUser(
            '別の受講生',
            'other-student-index-test@example.com',
            'student',
        );

        $coach = $this->createUser(
            'テストコーチ',
            'coach-index-test@example.com',
            'coach',
        );

        $category = CertificationCategory::create([
            'name' => 'IndexTestカテゴリ',
            'slug' => 'index-test-category',
        ]);

        $certification = Certification::create([
            'category_id' => $category->id,
            'name' => 'IndexTest資格',
            'description' => 'IndexActionテスト用資格',
            'difficulty' => 'beginner',
            'status' => 'published',
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'certification_id' => $certification->id,
        ]);

        $otherEnrollment = Enrollment::create([
            'user_id' => $otherStudent->id,
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
            'otherStudent' => $otherStudent,
            'coach' => $coach,
            'certification' => $certification,
            'enrollment' => $enrollment,
            'otherEnrollment' => $otherEnrollment,
        ];
    }

    /**
     * 面談を直接作成する。
     */
    private function createMeeting(
        Enrollment $enrollment,
        User $student,
        User $coach,
        Carbon $scheduledAt,
        MeetingStatus $status,
        string $topic,
    ): Meeting {
        return Meeting::create([
            'enrollment_id' => $enrollment->id,
            'coach_id' => $coach->id,
            'student_id' => $student->id,
            'scheduled_at' => $scheduledAt,
            'status' => $status->value,
            'topic' => $topic,
            'meeting_url_snapshot' => 'https://example.com/meeting',
        ]);
    }

    /**
     * upcomingでは未来の予約済み面談だけ取得できる。
     */
    public function test_upcomingでは未来の予約済み面談だけ取得できる(): void
    {
        $data = $this->createTestData();

        $futureMeeting = $this->createMeeting(
            $data['enrollment'],
            $data['student'],
            $data['coach'],
            Carbon::now()->addDay(),
            MeetingStatus::Reserved,
            '未来の面談',
        );

        $this->createMeeting(
            $data['enrollment'],
            $data['student'],
            $data['coach'],
            Carbon::now()->subDay(),
            MeetingStatus::Reserved,
            '過去の予約済み面談',
        );

        $this->createMeeting(
            $data['enrollment'],
            $data['student'],
            $data['coach'],
            Carbon::now()->addDays(2),
            MeetingStatus::Canceled,
            'キャンセル済み面談',
        );

        $action = new IndexAction;

        $result = $action($data['student'], 'upcoming');

        $this->assertSame(1, $result->total());
        $this->assertTrue(
            $result->getCollection()->contains('id', $futureMeeting->id)
        );
    }

    /**
     * pastではキャンセル済み・完了済みだけ取得できる。
     */
    public function test_pastではキャンセル済みと完了済みだけ取得できる(): void
    {
        $data = $this->createTestData();

        $canceledMeeting = $this->createMeeting(
            $data['enrollment'],
            $data['student'],
            $data['coach'],
            Carbon::now()->subDays(2),
            MeetingStatus::Canceled,
            'キャンセル済み面談',
        );

        $completedMeeting = $this->createMeeting(
            $data['enrollment'],
            $data['student'],
            $data['coach'],
            Carbon::now()->subDay(),
            MeetingStatus::Completed,
            '完了済み面談',
        );

        $this->createMeeting(
            $data['enrollment'],
            $data['student'],
            $data['coach'],
            Carbon::now()->addDay(),
            MeetingStatus::Reserved,
            '未来の予約済み面談',
        );

        $action = new IndexAction;

        $result = $action($data['student'], 'past');

        $this->assertSame(2, $result->total());

        $this->assertTrue(
            $result->getCollection()->contains('id', $canceledMeeting->id)
        );

        $this->assertTrue(
            $result->getCollection()->contains('id', $completedMeeting->id)
        );
    }

    /**
     * allでは対象受講生の面談をすべて取得できる。
     */
    public function test_allでは対象受講生の面談をすべて取得できる(): void
    {
        $data = $this->createTestData();

        $upcomingMeeting = $this->createMeeting(
            $data['enrollment'],
            $data['student'],
            $data['coach'],
            Carbon::now()->addDay(),
            MeetingStatus::Reserved,
            '未来の面談',
        );

        $pastMeeting = $this->createMeeting(
            $data['enrollment'],
            $data['student'],
            $data['coach'],
            Carbon::now()->subDay(),
            MeetingStatus::Completed,
            '過去の面談',
        );

        $this->createMeeting(
            $data['otherEnrollment'],
            $data['otherStudent'],
            $data['coach'],
            Carbon::now()->addDays(1)->addHour(),
            MeetingStatus::Reserved,
            '別受講生の面談',
        );

        $action = new IndexAction;

        $result = $action($data['student'], 'all');

        $this->assertSame(2, $result->total());

        $this->assertTrue(
            $result->getCollection()->contains('id', $upcomingMeeting->id)
        );

        $this->assertTrue(
            $result->getCollection()->contains('id', $pastMeeting->id)
        );
    }

    /**
     * 不正なfilterはupcomingとして扱われる。
     */
    public function test_不正なfilterはupcomingとして扱われる(): void
    {
        $data = $this->createTestData();

        $futureMeeting = $this->createMeeting(
            $data['enrollment'],
            $data['student'],
            $data['coach'],
            Carbon::now()->addDay(),
            MeetingStatus::Reserved,
            '未来の面談',
        );

        $this->createMeeting(
            $data['enrollment'],
            $data['student'],
            $data['coach'],
            Carbon::now()->subDay(),
            MeetingStatus::Completed,
            '過去の面談',
        );

        $action = new IndexAction;

        $result = $action($data['student'], 'invalid');

        $this->assertSame(1, $result->total());

        $this->assertTrue(
            $result->getCollection()->contains('id', $futureMeeting->id)
        );
    }
}
