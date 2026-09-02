<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Meeting;

use App\Models\Certification;
use App\Models\CertificationCategory;
use App\Models\CoachAvailability;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Services\MeetingAvailabilityService;
use App\UseCases\Meeting\FindAvailableAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class FindAvailableTest extends TestCase
{
    use RefreshDatabase;

    private function createTestData(): array
    {
        $student = User::create([
            'name' => 'FindAvailableTest受講生',
            'email' => 'student-find-available-test@example.com',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);

        $coach = User::create([
            'name' => 'FindAvailableTestコーチ',
            'email' => 'coach-find-available-test@example.com',
            'password' => bcrypt('password'),
            'role' => 'coach',
        ]);

        $category = CertificationCategory::create([
            'name' => 'FindAvailableTestカテゴリ',
            'slug' => 'find-available-test-category',
        ]);

        $certification = Certification::create([
            'category_id' => $category->id,
            'name' => 'FindAvailableTest資格',
            'description' => 'FindAvailableActionテスト用資格',
            'difficulty' => 'beginner',
            'status' => 'published',
            'created_by_user_id' => $coach->id,
            'updated_by_user_id' => $coach->id,
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'certification_id' => $certification->id,
        ]);

        $certification->coaches()->attach(
            $coach->id,
            [
                'assigned_by_user_id' => $coach->id,
                'assigned_at' => now(),
            ]
        );

        return compact(
            'student',
            'coach',
            'category',
            'certification',
            'enrollment',
        );
    }

    private function createGoogleCalendarMock(
        array $events = [],
    ): GoogleCalendarService {
        $mock = Mockery::mock(GoogleCalendarService::class);

        $mock->shouldReceive('eventsForCoach')
            ->andReturn($events);

        return $mock;
    }

    private function createAction(
        array $events = [],
    ): FindAvailableAction {
        return new FindAvailableAction(
            new MeetingAvailabilityService(
                $this->createGoogleCalendarMock($events),
            ),
        );
    }

    public function test_担当コーチの空きスロットを取得できる(): void
    {
        $data = $this->createTestData();

        $date = Carbon::tomorrow()->startOfDay();

        CoachAvailability::create([
            'coach_id' => $data['coach']->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '18:00:00',
            'end_time' => '21:00:00',
            'is_active' => true,
        ]);

        $action = $this->createAction();

        $result = ($action)(
            $data['enrollment'],
            $date->format('Y-m-d'),
        );

        $this->assertCount(3, $result);

        $this->assertSame(
            '18:00',
            $result->first()['slot_start']->format('H:i'),
        );

        $this->assertSame(
            '20:00',
            $result->last()['slot_start']->format('H:i'),
        );

        $this->assertSame(
            1,
            $result->first()['available_coach_count'],
        );
    }

    public function test_担当コーチがいなければ空のコレクションを返す(): void
    {
        $data = $this->createTestData();

        $data['certification']->coaches()->detach();

        $action = $this->createAction();

        $result = ($action)(
            $data['enrollment'],
            Carbon::tomorrow()->format('Y-m-d'),
        );

        $this->assertCount(0, $result);
    }

    public function test_google_calendarの予定がある時間帯は空きスロットから除外される(): void
    {
        $data = $this->createTestData();

        $date = Carbon::tomorrow()->startOfDay();

        CoachAvailability::create([
            'coach_id' => $data['coach']->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '18:00:00',
            'end_time' => '21:00:00',
            'is_active' => true,
        ]);

        $googleEventStart = Carbon::parse(
            $date->format('Y-m-d').' 19:00:00'
        );

        $googleEventEnd = $googleEventStart->copy()->addHour();

        $events = [[
            'start' => $googleEventStart,
            'end' => $googleEventEnd,
        ]];

        $action = $this->createAction($events);

        $result = ($action)(
            $data['enrollment'],
            $date->format('Y-m-d'),
        );

        $times = $result
            ->map(fn (array $slot) => $slot['slot_start']->format('H:i'))
            ->all();

        $this->assertSame(
            ['18:00', '20:00'],
            $times,
        );
    }
}
