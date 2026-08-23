<?php

namespace App\Http\Controllers;

use Google\Client;
use Google\Service\Calendar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\GoogleCalendarToken;

class GoogleCalendarController extends Controller
{
    /**
     * Google Calendarと連携する。
     */
    public function connect(): RedirectResponse
    {
        $client = new Client();

        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));

        $client->addScope('https://www.googleapis.com/auth/calendar.events');
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return redirect()->away($client->createAuthUrl());

    }

     /**
     * Google認証後のコールバック。
     */
    public function callback(Request $request): RedirectResponse
    {
        if (!$request->has('code')) {
            abort(400, 'Google認証に失敗しました。');
        }

        $client = new Client();

        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));

        $token = $client->fetchAccessTokenWithAuthCode(
            $request->string('code')->toString()
        );

        if (isset($token['error'])) {
            dd($token);
        }

        GoogleCalendarToken::updateOrCreate(
            ['user_id' => auth()->id()],
            [
            'access_token' => $token['access_token'],
            'refresh_token' => $token['refresh_token'] ?? null,
            'expires_at' => now()->addSeconds($token['expires_in']),
            'refresh_token_expires_at' => isset($token['refresh_token_expires_in'])
            ? now()->addSeconds($token['refresh_token_expires_in'])
            : null,
            ]
        );

        return redirect('/settings/availability')
            ->with('message', 'Google Calendarとの連携に成功しました。');
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
}
