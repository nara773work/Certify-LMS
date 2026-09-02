<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GoogleCalendarToken;
use App\Models\Meeting;
use Carbon\Carbon;
use Throwable;

class GoogleCalendarService
{
    public function __construct(
        private readonly GoogleCalendarGateway $gateway,
    ) {}

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

        // Google未連携なら予定なし
        if ($credential === null) {
            return [];
        }

        try {
            return $this->executeWithRefresh(
                $credential,
                fn () => $this->gateway->listEvents(
                    $credential,
                    $dayStart,
                    $dayEnd
                )
            );
        } catch (Throwable) {
            // Google側の障害などで取得できない場合は
            // LMS側では予定なしとして扱う。
            return [];
        }
    }

    /**
     * Google Calendarに面談予定を作成する。
     */
    public function createEvent(Meeting $meeting): ?string
    {
        $credential = GoogleCalendarToken::where(
            'user_id',
            $meeting->coach_id
        )->first();

        if ($credential === null) {
            return null;
        }

        try {
            return $this->executeWithRefresh(
                $credential,
                fn () => $this->gateway->createEvent(
                    $credential,
                    $meeting
                )
            );
        } catch (Throwable) {
            // Google Calendarへの登録に失敗しても
            // LMSの面談予約自体は失敗させない。
            return null;
        }
    }

    /**
     * Google Calendarから面談予定を削除する。
     */
    public function deleteEvent(Meeting $meeting): void
    {
        if ($meeting->google_calendar_event_id === null) {
            return;
        }

        $credential = GoogleCalendarToken::where(
            'user_id',
            $meeting->coach_id
        )->first();

        if ($credential === null) {
            return;
        }

        try {
            $this->executeWithRefresh(
                $credential,
                function () use ($credential, $meeting): null {
                    $this->gateway->deleteEvent(
                        $credential,
                        $meeting->google_calendar_event_id
                    );

                    return null;
                }
            );
        } catch (Throwable) {
            // Google側ですでに削除されている場合などは
            // LMS側のキャンセル処理を失敗させない。
        }
    }

    /**
     * Google Calendar連携済みか。
     */
    public function isConnected(string $coachId): bool
    {
        return GoogleCalendarToken::where(
            'user_id',
            $coachId
        )->exists();
    }

    /**
     * トークン期限を確認し、必要なら更新して処理する。
     *
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    private function executeWithRefresh(
        GoogleCalendarToken $credential,
        callable $callback,
    ): mixed {
        // アクセストークンがまだ有効
        if (
            $credential->expires_at !== null
            && $credential->expires_at->isFuture()
        ) {
            try {
                return $callback();
            } catch (Throwable $firstException) {
                // Google側から認証エラーが返った場合は
                // 下のリフレッシュ処理へ進む。
            }
        }

        // リフレッシュトークン自体が期限切れなら
        // リフレッシュできない。
        if (
            $credential->refresh_token_expires_at !== null
            && $credential->refresh_token_expires_at->isPast()
        ) {
            throw new \RuntimeException(
                'Google Calendar refresh token has expired.'
            );
        }

        $token = $this->gateway->refreshAccessToken($credential);

        if (
            ! isset($token['access_token'])
            || $token['access_token'] === ''
        ) {
            throw new \RuntimeException(
                'Failed to refresh Google Calendar access token.'
            );
        }

        $credential->update([
            'access_token' => $token['access_token'],
            'expires_at' => now()->addSeconds(
                (int) ($token['expires_in'] ?? 3600)
            ),
        ]);

        return $callback();
    }
}
