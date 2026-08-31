<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GoogleCalendarToken;
use App\Models\Meeting;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Calendar;

class GoogleCalendarGateway
{
    /**
     * Google Calendarから予定を取得する。
     *
     * @return array<int, array{start: Carbon, end: Carbon}>
     */
    public function listEvents(
        GoogleCalendarToken $credential,
        Carbon $dayStart,
        Carbon $dayEnd,
    ): array {
        $client = $this->createClient($credential);
        $calendar = new Calendar($client);

        $events = $calendar->events->listEvents('primary', [
            'timeMin' => $dayStart->toRfc3339String(),
            'timeMax' => $dayEnd->toRfc3339String(),
            'singleEvents' => true,
            'orderBy' => 'startTime',
        ]);

        return collect($events->getItems())
            ->filter(
                fn ($event) =>
                    $event->getStart()?->getDateTime()
                    && $event->getEnd()?->getDateTime()
            )
            ->map(fn ($event) => [
                'start' => Carbon::parse(
                    $event->getStart()->getDateTime()
                ),
                'end' => Carbon::parse(
                    $event->getEnd()->getDateTime()
                ),
            ])
            ->values()
            ->all();
    }

    /**
     * Google Calendarに面談予定を作成する。
     */
    public function createEvent(
        GoogleCalendarToken $credential,
        Meeting $meeting,
    ): ?string {
        $client = $this->createClient($credential);
        $calendar = new Calendar($client);

        $event = new Calendar\Event([
            'summary' => '面談',
            'description' => $meeting->topic,
            'start' => [
                'dateTime' => $meeting->scheduled_at
                    ->toRfc3339String(),
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

        $createdEvent = $calendar->events->insert(
            'primary',
            $event
        );

        return $createdEvent->getId();
    }

    /**
     * Google Calendarから予定を削除する。
     */
    public function deleteEvent(
        GoogleCalendarToken $credential,
        string $eventId,
    ): void {
        $client = $this->createClient($credential);
        $calendar = new Calendar($client);

        $calendar->events->delete(
            'primary',
            $eventId
        );
    }

    /**
     * リフレッシュトークンを使ってアクセストークンを更新する。
     *
     * @return array<string, mixed>
     */
    public function refreshAccessToken(
        GoogleCalendarToken $credential,
    ): array {
        $client = $this->createClient($credential);

        return $client->fetchAccessTokenWithRefreshToken(
            $credential->refresh_token
        );
    }

    /**
     * Google Clientを生成する。
     */
    private function createClient(
        GoogleCalendarToken $credential
    ): Client {
        $client = new Client();

        $client->setClientId(
            config('services.google.client_id')
        );

        $client->setClientSecret(
            config('services.google.client_secret')
        );

        $client->setAccessToken([
            'access_token' => $credential->access_token,
            'refresh_token' => $credential->refresh_token,
        ]);

        return $client;
    }
}