<?php

namespace Tests\Feature\Http\Meeting;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\GoogleCalendarToken;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;
use App\Models\Enrollment;

class MeetingAvailabilityTest extends TestCase
{
    /**
     * Google Calendar連携済みコーチに予定がある時間帯は
     * 予約可能枠から除外される。
     */
    use RefreshDatabase;

public function test_google_calendar_event_is_excluded_from_available_slots(): void
{
    $this->seed();

    $coach = User::where('email', 'coach@certify-lms.test')->first();

    $this->assertNotNull($coach);

    GoogleCalendarToken::create([
        'user_id' => $coach->id,
        'access_token' => 'test-access-token',
        'refresh_token' => 'test-refresh-token',
        'expires_at' => now()->addHour(),
        'refresh_token_expires_at' => now()->addDays(30),
    ]);

    $googleCalendarService = Mockery::mock(GoogleCalendarService::class);

    $googleCalendarService
        ->shouldReceive('eventsForCoach')
        ->once()
        ->with(
            (string) $coach->id,
            Mockery::type(Carbon::class),
            Mockery::type(Carbon::class),
        )
        ->andReturn([
            [
                'start' => Carbon::parse('2026-08-24 10:00:00'),
                'end' => Carbon::parse('2026-08-24 11:00:00'),
            ],
        ]);

    $this->app->instance(
        GoogleCalendarService::class,
        $googleCalendarService
    );

    $enrollment = Enrollment::whereHas('certification', function ($query) {
    $query->where('name', '基本情報技術者試験');
})->first();

    $this->assertNotNull($enrollment);

    $response = $this
        ->actingAs($enrollment->user)
        ->getJson(
            route('meetings.availability', [
                'enrollment' => $enrollment,
                'date' => '2026-08-24',
            ])
        );

    $response->assertOk();

    $slots = $response->json('slots');

    $this->assertFalse(
        collect($slots)->contains(
            fn (array $slot): bool =>
                str_contains($slot['slot_start'], '10:00:00')
        )
    );
}

    /**
     * Google Calendar未連携コーチはGoogle予定による制限を受けず、
     * 従来通りの空き判定が行われる。
     */
    public function test_unconnected_coach_is_available_normally(): void
{
    $this->seed();

    $coach = User::where('email', 'coach@certify-lms.test')->first();

    $this->assertNotNull($coach);

    // GoogleCalendarTokenは作成しない。

    $googleCalendarService = Mockery::mock(GoogleCalendarService::class);

    $googleCalendarService
        ->shouldReceive('eventsForCoach')
        ->once()
        ->with(
            (string) $coach->id,
            Mockery::type(Carbon::class),
            Mockery::type(Carbon::class),
        )
        ->andReturn([]);

    $this->app->instance(
        GoogleCalendarService::class,
        $googleCalendarService
    );

    $enrollment = Enrollment::whereHas('certification', function ($query) {
        $query->where('name', '基本情報技術者試験');
    })->first();

    $this->assertNotNull($enrollment);

    $response = $this
        ->actingAs($enrollment->user)
        ->getJson(
            route('meetings.availability', [
                'enrollment' => $enrollment,
                'date' => '2026-08-24',
            ])
        );

    $response->assertOk();

    $slots = $response->json('slots');

    $this->assertNotEmpty($slots);
}
}
