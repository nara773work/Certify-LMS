<?php

namespace Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\GoogleCalendarToken;
use App\Models\User;
use Mockery;
/**
 * @group external-api
 */
class GoogleCalendarControllerTest extends TestCase
{
    /**
     * コーチはGoogle Calendar連携画面からGoogle認証へリダイレクトできる。
     */
    use RefreshDatabase;

    public function test_coach_can_start_google_calendar_connection(): void
    {
        $this->seed();
        $coach = User::where('email', 'coach@certify-lms.test')->first();

        $response = $this
            ->actingAs($coach)
            ->get(route('settings.google-calendar.redirect'));

        $response->assertRedirect();
    }

    /**
     * 連携済みコーチはGoogle Calendar連携を解除できる。
     */
    public function test_coach_can_disconnect_google_calendar(): void
    {
        $this->seed();
        $coach = User::where('email', 'coach@certify-lms.test')->first();

        GoogleCalendarToken::create([
            'user_id' => $coach->id,
            'access_token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'expires_at' => now()->addHour(),
            'refresh_token_expires_at' => now()->addDays(30),
        ]);

        $response = $this
            ->actingAs($coach)
            ->delete(route('settings.google-calendar.destroy'));

        $response->assertRedirect();

        $this->assertDatabaseMissing('google_calendar_tokens', [
            'user_id' => $coach->id,
        ]);
    }

        public function test_coach_can_complete_google_calendar_callback(): void
{
    $this->seed();

    $coach = User::where(
        'email',
        'coach@certify-lms.test'
    )->first();

    $this->actingAs($coach);

    $client = Mockery::mock(\Google\Client::class);

    $client
        ->shouldReceive('setClientId')
        ->once();

    $client
        ->shouldReceive('setClientSecret')
        ->once();

    $client
        ->shouldReceive('setRedirectUri')
        ->once();

    $client
        ->shouldReceive('fetchAccessTokenWithAuthCode')
        ->once()
        ->with('test-auth-code')
        ->andReturn([
            'access_token' => 'new-access-token',
            'refresh_token' => 'new-refresh-token',
            'expires_in' => 3600,
            'refresh_token_expires_in' => 2592000,
        ]);

    $this->app->bind(
        \Google\Client::class,
        fn () => $client
    );

    $response = $this->get(
        route('settings.google-calendar.callback', [
            'code' => 'test-auth-code',
        ])
    );

    $response->assertRedirect('/settings/availability');

    $response->assertSessionHas(
        'message',
        'Google Calendarとの連携に成功しました。'
    );

    $this->assertDatabaseHas('google_calendar_tokens', [
        'user_id' => $coach->id,
        'access_token' => 'new-access-token',
        'refresh_token' => 'new-refresh-token',
    ]);
}

public function test_callback_without_code_returns_400(): void
{
    $this->seed();

    $coach = User::where(
        'email',
        'coach@certify-lms.test'
    )->first();

    $this->actingAs($coach);

    $beforeCount = GoogleCalendarToken::count();

    $response = $this->get(
        route('settings.google-calendar.callback')
    );

    $response->assertStatus(400);

    $this->assertSame(
        $beforeCount,
        GoogleCalendarToken::count()
    );
}
}
