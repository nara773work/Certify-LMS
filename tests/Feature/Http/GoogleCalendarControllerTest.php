<?php

namespace Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\GoogleCalendarToken;
use App\Models\User;

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
}
