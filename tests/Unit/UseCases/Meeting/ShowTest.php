<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Meeting;

use App\Enums\MeetingStatus;
use App\Models\Certification;
use App\Models\CertificationCategory;
use App\Models\Enrollment;
use App\Models\Meeting;
use App\Models\User;
use App\UseCases\Meeting\ShowAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    private function createTestData(): array
    {
        $student = User::create([
            'name' => 'ShowTest受講生',
            'email' => 'student-show-test@example.com',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);

        $coach = User::create([
            'name' => 'ShowTestコーチ',
            'email' => 'coach-show-test@example.com',
            'password' => bcrypt('password'),
            'role' => 'coach',
        ]);

        $category = CertificationCategory::create([
            'name' => 'ShowTestカテゴリ',
            'slug' => 'show-test-category',
        ]);

        $certification = Certification::create([
            'category_id' => $category->id,
            'name' => 'ShowTest資格',
            'description' => 'ShowActionテスト用資格',
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
            'topic' => 'ShowActionテスト面談',
            'meeting_url_snapshot' => 'https://example.com/meeting',
        ]);

        return compact(
            'student',
            'coach',
            'category',
            'certification',
            'enrollment',
            'meeting',
        );
    }

    public function test_面談詳細を取得できる(): void
    {
        $data = $this->createTestData();

        $action = app(ShowAction::class);

        $result = ($action)($data['meeting']);

        $this->assertSame(
            $data['meeting']->id,
            $result->id,
        );

        $this->assertSame(
            $data['meeting']->enrollment_id,
            $result->enrollment_id,
        );

        $this->assertSame(
            $data['coach']->id,
            $result->coach_id,
        );

        $this->assertSame(
            $data['student']->id,
            $result->student_id,
        );

        $this->assertSame(
            MeetingStatus::Reserved,
            $result->status,
        );
    }

    public function test_必要なリレーションがロードされる(): void
    {
        $data = $this->createTestData();

        $action = app(ShowAction::class);

        $result = ($action)($data['meeting']);

        $this->assertTrue($result->relationLoaded('enrollment'));
        $this->assertTrue($result->relationLoaded('coach'));
        $this->assertTrue($result->relationLoaded('student'));
        $this->assertTrue($result->relationLoaded('canceledBy'));
        $this->assertTrue($result->relationLoaded('meetingMemo'));

        $this->assertTrue($result->relationLoaded('enrollment'));

        $this->assertTrue(
            $result->enrollment->relationLoaded('certification')
        );
    }

    public function test_面談の関連データを正しく取得できる(): void
    {
        $data = $this->createTestData();

        $action = app(ShowAction::class);

        $result = ($action)($data['meeting']);

        $this->assertSame(
            $data['certification']->id,
            $result->enrollment->certification->id,
        );

        $this->assertSame(
            $data['coach']->id,
            $result->coach->id,
        );

        $this->assertSame(
            $data['student']->id,
            $result->student->id,
        );

        $this->assertNull($result->canceledBy);
        $this->assertNull($result->meetingMemo);
    }
}
