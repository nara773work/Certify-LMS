<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\GoogleCalendarToken;
use App\Models\Meeting;
use App\Models\User;
use App\Services\GoogleCalendarGateway;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

final class GoogleCalendarServiceTest extends TestCase
{
    use RefreshDatabase;

    private GoogleCalendarGateway $gateway;

    private GoogleCalendarService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = Mockery::mock(
            GoogleCalendarGateway::class
        );

        $this->service = new GoogleCalendarService(
            $this->gateway
        );
    }

    public function test_google未連携なら予定は空配列(): void
    {
        $coach = User::factory()->coach()->create();

        $events = $this->service->eventsForCoach(
            $coach->id,
            Carbon::parse('2026-09-01 00:00:00'),
            Carbon::parse('2026-09-02 00:00:00'),
        );

        $this->assertSame([], $events);

        $this->gateway
            ->shouldNotReceive('listEvents');
    }

    public function test_google連携済みなら予定を取得する(): void
    {
        $coach = User::factory()->coach()->create();

        $token = $this->createToken(
            $coach,
            expiresAt: now()->addHour()
        );

        $start = Carbon::parse('2026-09-01 10:00:00');
        $end = Carbon::parse('2026-09-01 11:00:00');

        $this->gateway
            ->shouldReceive('listEvents')
            ->once()
            ->with(
                Mockery::on(
                    fn ($value) => $value->id === $token->id
                ),
                Mockery::type(Carbon::class),
                Mockery::type(Carbon::class),
            )
            ->andReturn([
                [
                    'start' => $start,
                    'end' => $end,
                ],
            ]);

        $events = $this->service->eventsForCoach(
            $coach->id,
            $start->copy()->startOfDay(),
            $start->copy()->endOfDay(),
        );

        $this->assertCount(1, $events);
        $this->assertTrue(
            $events[0]['start']->equalTo($start)
        );
        $this->assertTrue(
            $events[0]['end']->equalTo($end)
        );
    }

    public function testアクセストークン期限切れならリフレッシュして再試行する(): void
    {
        $coach = User::factory()->coach()->create();

        $token = $this->createToken(
            $coach,
            expiresAt: now()->subMinute()
        );

        $newAccessToken = 'new-access-token';

        $this->gateway
            ->shouldReceive('refreshAccessToken')
            ->once()
            ->with(
                Mockery::on(
                    fn ($value) => $value->id === $token->id
                )
            )
            ->andReturn([
                'access_token' => $newAccessToken,
                'expires_in' => 3600,
            ]);

        $this->gateway
            ->shouldReceive('listEvents')
            ->once()
            ->andReturn([]);

        $this->service->eventsForCoach(
            $coach->id,
            now()->startOfDay(),
            now()->endOfDay(),
        );

        $token->refresh();

        $this->assertSame(
            $newAccessToken,
            $token->access_token
        );

        $this->assertTrue(
            $token->expires_at->isFuture()
        );
    }

    public function testリフレッシュトークン期限切れなら予定取得は空配列(): void
    {
        $coach = User::factory()->coach()->create();

        $this->createToken(
            $coach,
            expiresAt: now()->subMinute(),
            refreshTokenExpiresAt: now()->subMinute()
        );

        $this->gateway
            ->shouldNotReceive('refreshAccessToken');

        $events = $this->service->eventsForCoach(
            $coach->id,
            now()->startOfDay(),
            now()->endOfDay(),
        );

        $this->assertSame([], $events);
    }

    public function testリフレッシュに失敗した場合は予定取得を空配列にする(): void
    {
        $coach = User::factory()->coach()->create();

        $token = $this->createToken(
            $coach,
            expiresAt: now()->subMinute()
        );

        $this->gateway
            ->shouldReceive('refreshAccessToken')
            ->once()
            ->with(
                Mockery::on(
                    fn ($value) => $value->id === $token->id
                )
            )
            ->andThrow(
                new \RuntimeException('refresh failed')
            );

        $events = $this->service->eventsForCoach(
            $coach->id,
            now()->startOfDay(),
            now()->endOfDay(),
        );

        $this->assertSame([], $events);
    }

    public function test連携済みならGoogleCalendarに面談を作成する(): void
    {
        $coach = User::factory()->coach()->create();

        $student = User::factory()->student()->create();

        $token = $this->createToken(
            $coach,
            expiresAt: now()->addHour()
        );

        $meeting = Meeting::factory()->create([
            'coach_id' => $coach->id,
            'student_id' => $student->id,
            'topic' => 'テスト面談',
            'scheduled_at' => now()->addDay(),
        ]);

        $this->gateway
            ->shouldReceive('createEvent')
            ->once()
            ->with(
                Mockery::on(
                    fn ($value) => $value->id === $token->id
                ),
                Mockery::on(
                    fn ($value) => $value->id === $meeting->id
                ),
            )
            ->andReturn('google-event-123');

        $eventId = $this->service->createEvent($meeting);

        $this->assertSame(
            'google-event-123',
            $eventId
        );
    }

    public function test未連携ならGoogleCalendarイベントを作成しない(): void
    {
        $coach = User::factory()->coach()->create();

        $student = User::factory()->student()->create();

        $meeting = Meeting::factory()->create([
            'coach_id' => $coach->id,
            'student_id' => $student->id,
            'scheduled_at' => now()->addDay(),
        ]);

        $this->gateway
            ->shouldNotReceive('createEvent');

        $eventId = $this->service->createEvent($meeting);

        $this->assertNull($eventId);
    }

    public function testGoogleCalendarイベントを削除する(): void
    {
        $coach = User::factory()->coach()->create();

        $student = User::factory()->student()->create();

        $token = $this->createToken(
            $coach,
            expiresAt: now()->addHour()
        );

        $meeting = Meeting::factory()->create([
            'coach_id' => $coach->id,
            'student_id' => $student->id,
            'scheduled_at' => now()->addDay(),
            'google_calendar_event_id' => 'google-event-123',
        ]);

        $this->gateway
            ->shouldReceive('deleteEvent')
            ->once()
            ->with(
                Mockery::on(
                    fn ($value) => $value->id === $token->id
                ),
                'google-event-123'
            );

        $this->service->deleteEvent($meeting);

        $this->assertTrue(true);
    }

    public function testイベントIDがなければ削除しない(): void
    {
        $coach = User::factory()->coach()->create();

        $student = User::factory()->student()->create();

        $meeting = Meeting::factory()->create([
            'coach_id' => $coach->id,
            'student_id' => $student->id,
            'scheduled_at' => now()->addDay(),
            'google_calendar_event_id' => null,
        ]);

        $this->gateway
            ->shouldNotReceive('deleteEvent');

        $this->service->deleteEvent($meeting);

        $this->assertTrue(true);
    }

    public function testisConnectedは連携状態を返す(): void
    {
        $connectedCoach = User::factory()->coach()->create();
        $unconnectedCoach = User::factory()->coach()->create();

        $this->createToken(
            $connectedCoach,
            expiresAt: now()->addHour()
        );

        $this->assertTrue(
            $this->service->isConnected($connectedCoach->id)
        );

        $this->assertFalse(
            $this->service->isConnected($unconnectedCoach->id)
        );
    }

    private function createToken(
        User $coach,
        Carbon $expiresAt,
        ?Carbon $refreshTokenExpiresAt = null,
    ): GoogleCalendarToken {
        return GoogleCalendarToken::create([
            'user_id' => $coach->id,
            'access_token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'expires_at' => $expiresAt,
            'refresh_token_expires_at' =>
                $refreshTokenExpiresAt
                ?? now()->addDays(30),
        ]);
    }
}