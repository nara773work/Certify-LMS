<?php

namespace App\Http\Controllers;

use Google\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\GoogleCalendarToken;
use App\Services\GoogleCalendarService;
use App\Http\Requests\AvailabilityRequest;
use App\Services\MeetingAvailabilityService;
use Carbon\Carbon;
use App\Models\Enrollment;

class GoogleCalendarController extends Controller
{
    /**
     * Google Calendarと連携する。
     */

    public function __construct(
        private readonly Client $client,
    ) {
    }
    public function connect(): RedirectResponse
{
    $client = $this->client;

    $client->setClientId(config('services.google.client_id'));
    $client->setClientSecret(config('services.google.client_secret'));
    $client->setRedirectUri(config('services.google.redirect'));

    $client->addScope(
        'https://www.googleapis.com/auth/calendar.events'
    );

    $client->setAccessType('offline');
    $client->setPrompt('consent');

    return redirect()->away($client->createAuthUrl());
}

     /**
     * Google認証後のコールバック。
     */
    public function callback(Request $request): RedirectResponse
{
    if (! $request->has('code')) {
        abort(400, 'Google認証に失敗しました。');
    }

    $client = $this->client;

    $client->setClientId(config('services.google.client_id'));
    $client->setClientSecret(config('services.google.client_secret'));
    $client->setRedirectUri(config('services.google.redirect'));

    $token = $client->fetchAccessTokenWithAuthCode(
        $request->string('code')->toString()
    );

    if (isset($token['error'])) {
        abort(400, 'Google認証に失敗しました。');
    }

    GoogleCalendarToken::updateOrCreate(
        ['user_id' => auth()->id()],
        [
            'access_token' => $token['access_token'],
            'refresh_token' => $token['refresh_token'] ?? null,
            'expires_at' => now()->addSeconds($token['expires_in']),
            'refresh_token_expires_at' =>
                isset($token['refresh_token_expires_in'])
                    ? now()->addSeconds(
                        $token['refresh_token_expires_in']
                    )
                    : null,
        ]
    );

    return redirect('/settings/availability')
        ->with(
            'message',
            'Google Calendarとの連携に成功しました。'
        );
}

    /**
     * Google Calendarの連携を解除する。
     */
    public function destroy(): RedirectResponse
    {
        GoogleCalendarToken::where('user_id', auth()->id())->delete();

        return redirect('/settings/availability')
            ->with('message', 'Google Calendarとの連携を解除しました。');
    }

    public function fetchAvailability(
        
    Enrollment $enrollment,
    AvailabilityRequest $request,
    MeetingAvailabilityService $availabilityService,
    GoogleCalendarService $googleCalendarService,
): JsonResponse {
    $date = Carbon::parse($request->validated('date'));

    $slots = $availabilityService->slotsForCertification(
        $enrollment->loadMissing('certification')->certification,
        $date,
        $googleCalendarService,
    );

    return response()->json([
        'date' => $date->toDateString(),
        'slots' => $slots->map(fn (array $slot) => [
            'slot_start' => $slot['slot_start']->toIso8601String(),
            'slot_end' => $slot['slot_end']->toIso8601String(),
            'available_coach_count' => $slot['available_coach_count'],
        ])->all(),
    ]);
}

}
