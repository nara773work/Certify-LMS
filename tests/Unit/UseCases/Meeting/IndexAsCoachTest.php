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
use App\UseCases\Meeting\IndexAsCoachAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class IndexAsCoachTest extends TestCase
{
    use RefreshDatabase;

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

    private function createTestData(): array
    {
        $admin = $this->createUser(
            'テスト管理者',
            'admin-index-coach-test@example.com',
            'admin',
        );

        $coach = $this->createUser(
            'テストコーチ',
            'coach-index-coach-test@example.com',
            'coach',
        );

        $otherCoach = $this->createUser(
            '別コーチ',
            'other-coach-index-coach-test@example.com',
            'coach',
        );

        $student = $this->createUser(
            'テスト受講生',
            'student-index-coach-test@example.com',
            'student',
        );

        $student2 = $this->createUser(
            '別の受講生',
            'student2-index-coach-test@example.com',
            'student',
        );

        $category = CertificationCategory::create([
            'name' => 'IndexAsCoachTestカテゴリ',
            'slug' => 'index-as-coach-test-category',
        ]);

        $certification = Certification::create([
            'category_id' => $category->id,
            'name' => 'IndexAsCoachTest資格',
            'description' => 'IndexAsCoachActionテスト用資格',
            'difficulty' => 'beginner',
            'status' => 'published',
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'certification_id' => $certification->id,
        ]);

        $enrollment2 = Enrollment::create([
            'user_id' => $student2->id,
            'certification_id' => $certification->id,
        ]);

        CertificationCoachAssignment::create([
            'certification_id' => $certification->id,
            'user_id' => $coach->id,
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'unassigned_at' => null,
        ]);

        CertificationCoachAssignment::create([
            'certification_id' => $certification->id,
            'user_id' => $otherCoach->id,
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'unassigned_at' => null,
        ]);

        return [
            'admin' => $admin,
            'coach' => $coach,
            'otherCoach' => $otherCoach,
            'student' => $student,
            'student2' => $student2,
            'certification' => $certification,
            'enrollment' => $enrollment,
            'enrollment2' => $enrollment2,
        ];
    }

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
     * upcomingでは担当コーチの未来の予約済み面談だけ取得できる。
     */
    public function test_upcomingでは担当コーチの未来の予約済み面談だけ取得できる(): void
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

        $otherCoachMeeting = $this->createMeeting(
            $data['enrollment'],
            $data['student'],
            $data['otherCoach'],
            Carbon::now()->addDay(),
            MeetingStatus::Reserved,
            '別コーチの面談',
        );

        $action = new IndexAsCoachAction;

        $result = $action($data['coach'], 'upcoming');

        $this->assertSame(1, $result->total());

        $this->assertTrue(
            $result->getCollection()->contains('id', $futureMeeting->id)
        );

        $this->assertFalse(
            $result->getCollection()->contains('id', $otherCoachMeeting->id)
        );
    }

    /**
     * pastでは担当コーチのキャンセル済み・完了済み面談だけ取得できる。
     */
    public function test_pastでは担当コーチの過去面談だけ取得できる(): void
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
            $data['enrollment2'],
            $data['student2'],
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
            '未来の面談',
        );

        $action = new IndexAsCoachAction;

        $result = $action($data['coach'], 'past');

        $this->assertSame(2, $result->total());

        $this->assertTrue(
            $result->getCollection()->contains('id', $canceledMeeting->id)
        );

        $this->assertTrue(
            $result->getCollection()->contains('id', $completedMeeting->id)
        );
    }

    /**
     * allでは担当コーチの面談をすべて取得できる。
     */
    public function test_allでは担当コーチの面談をすべて取得できる(): void
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
            $data['enrollment'],
            $data['student'],
            $data['otherCoach'],
            Carbon::now()->addDay(),
            MeetingStatus::Reserved,
            '別コーチの面談',
        );

        $action = new IndexAsCoachAction;

        $result = $action($data['coach'], 'all');

        $this->assertSame(2, $result->total());

        $this->assertTrue(
            $result->getCollection()->contains('id', $upcomingMeeting->id)
        );

        $this->assertTrue(
            $result->getCollection()->contains('id', $pastMeeting->id)
        );
    }

    /**
     * studentIdを指定すると受講生で絞り込める。
     */
    public function test_student_idで受講生を絞り込める(): void
    {
        $data = $this->createTestData();

        $meeting1 = $this->createMeeting(
            $data['enrollment'],
            $data['student'],
            $data['coach'],
            Carbon::now()->addDay(),
            MeetingStatus::Reserved,
            '受講生1の面談',
        );

        $meeting2 = $this->createMeeting(
            $data['enrollment2'],
            $data['student2'],
            $data['coach'],
            Carbon::now()->addDay()->addHour(),
            MeetingStatus::Reserved,
            '受講生2の面談',
        );

        $action = new IndexAsCoachAction;

        $result = $action(
            $data['coach'],
            'all',
            $data['student']->id,
        );

        $this->assertSame(1, $result->total());

        $this->assertTrue(
            $result->getCollection()->contains('id', $meeting1->id)
        );

        $this->assertFalse(
            $result->getCollection()->contains('id', $meeting2->id)
        );
    }

    /**
     * enrollmentIdを指定するとEnrollmentで絞り込める。
     */
    public function test_enrollment_idで受講登録を絞り込める(): void
    {
        $data = $this->createTestData();

        $meeting1 = $this->createMeeting(
            $data['enrollment'],
            $data['student'],
            $data['coach'],
            Carbon::now()->addDay(),
            MeetingStatus::Reserved,
            'Enrollment1の面談',
        );

        $meeting2 = $this->createMeeting(
            $data['enrollment2'],
            $data['student2'],
            $data['coach'],
            Carbon::now()->addDay()->addHour(),
            MeetingStatus::Reserved,
            'Enrollment2の面談',
        );

        $action = new IndexAsCoachAction;

        $result = $action(
            $data['coach'],
            'all',
            null,
            $data['enrollment']->id,
        );

        $this->assertSame(1, $result->total());

        $this->assertTrue(
            $result->getCollection()->contains('id', $meeting1->id)
        );

        $this->assertFalse(
            $result->getCollection()->contains('id', $meeting2->id)
        );
    }
}
