<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GoogleCalendarToken;
use App\Services\GoogleCalendarService;
use Google\Client;
use Google\Service\Calendar;
use Carbon\Carbon;
use App\Models\Meeting;

class GoogleCalendarService
{
    /**
     * 指定コーチのGoogle Calendar予定を取得する。
     *
     * @return array<int, array{start: Carbon, end: Carbon}>
     */
    public function eventsForCoach(
        string $coachId,
        Carbon $dayStart,
        Carbon $dayEnd,
    ): array {
        $credential = GoogleCalendarToken::where(
            'user_id',
            $coachId
        )->first();

        // Google未連携なら予定なしとして扱う
        if ($credential === null) {
            return [];
        }

        $client = new Client();

        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));

        $client->setAccessToken([
            'access_token' => $credential->access_token,
            'refresh_token' => $credential->refresh_token,
        ]);

        $service = new Calendar($client);

        $events = $service->events->listEvents('primary', [
            'timeMin' => $dayStart->toRfc3339String(),
            'timeMax' => $dayEnd->toRfc3339String(),
            'singleEvents' => true,
            'orderBy' => 'startTime',
        ]);

        return collect($events->getItems())
            ->filter(fn ($event) => $event->getStart()?->getDateTime())
            ->map(fn ($event) => [
                'start' => Carbon::parse($event->getStart()->getDateTime()),
                'end' => Carbon::parse($event->getEnd()->getDateTime()),
            ])
            ->values()
            ->all();
    }

    public function createEvent(Meeting $meeting): ?string
{
    $credential = GoogleCalendarToken::where(
        'user_id',
        $meeting->coach_id
    )->first();

    if ($credential === null) {
        return null;
    }

    $client = new Client();

    $client->setClientId(config('services.google.client_id'));
    $client->setClientSecret(config('services.google.client_secret'));

    $client->setAccessToken([
        'access_token' => $credential->access_token,
        'refresh_token' => $credential->refresh_token,
    ]);

    $service = new Calendar($client);

    $event = new Calendar\Event([
        'summary' => '面談',
        'description' => $meeting->topic,
        'start' => [
            'dateTime' => $meeting->scheduled_at->toRfc3339String(),
            'timeZone' => config('app.timezone'),
        ],
        'end' => [
            'dateTime' => $meeting->scheduled_at
                ->copy()
                ->addHour()
                ->toRfc3339String(),
            'timeZone' => config('app.timezone'),
        ],
    ]);

    $createdEvent = $service->events->insert('primary', $event);

    return $createdEvent->getId();
}

public function deleteEvent(Meeting $meeting): void
{
    $credential = GoogleCalendarToken::where(
        'user_id',
        $meeting->coach_id
    )->first();

    if ($credential === null) {
        return;
    }

    if ($meeting->google_calendar_event_id === null) {
        return;
    }

    $client = new Client();

    $client->setClientId(config('services.google.client_id'));
    $client->setClientSecret(config('services.google.client_secret'));

    $client->setAccessToken([
        'access_token' => $credential->access_token,
        'refresh_token' => $credential->refresh_token,
    ]);

    $service = new Calendar($client);

    $service->events->delete(
        'primary',
        $meeting->google_calendar_event_id
    );
}

public function isConnected(string $coachId): bool
    {
        return GoogleCalendarToken::where('user_id', $coachId)->exists();
}
}