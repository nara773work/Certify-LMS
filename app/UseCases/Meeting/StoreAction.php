<?php

declare(strict_types=1);

namespace App\Actions\Meeting;

use App\Actions\Meeting\ConsumeMeetingQuotaAction;
use App\Enums\MeetingStatus;
use App\Exceptions\InsufficientMeetingQuotaException;
use App\Exceptions\MeetingNoAvailableCoachException;
use App\Models\Certification;
use App\Models\Enrollment;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\MeetingReservationNotification;
use App\Services\GoogleCalendarService;
use App\Services\Meeting\MeetingAvailabilityService;
use App\Services\Meeting\MeetingCoachLoadService;
use App\Services\Meeting\MeetingQuotaService;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StoreAction
{
    public function __construct(
        private MeetingAvailabilityService $availabilityService,
        private MeetingCoachLoadService $coachLoadService,
        private MeetingQuotaService $quotaService,
        private ConsumeMeetingQuotaAction $consumeAction,
        private GoogleCalendarService $googleCalendarService,
    ) {
    }

    public function __invoke(
        Enrollment $enrollment,
        User $student,
        Carbon $scheduledAt,
        ?string $topic = null,
    ): Meeting {
        $meeting = DB::transaction(function () use (
            $enrollment,
            $student,
            $scheduledAt,
            $topic,
        ) {
            if ($this->quotaService->remaining($student) < 1) {
                throw new InsufficientMeetingQuotaException;
            }

            $this->availabilityService->validateSlot(
                $enrollment->certification,
                $scheduledAt
            );

            $candidates = $this->findAvailableCoaches(
                $enrollment->certification,
                $scheduledAt
            );

            if ($candidates->isEmpty()) {
                throw new MeetingNoAvailableCoachException;
            }

            $coach = $this->coachLoadService
                ->leastLoadedCoach($candidates);

            try {
                $meeting = Meeting::create([
                    'enrollment_id' => $enrollment->id,
                    'coach_id' => $coach->id,
                    'student_id' => $student->id,
                    'scheduled_at' => $scheduledAt,
                    'status' => MeetingStatus::Reserved->value,
                    'topic' => $topic,
                    'meeting_url_snapshot' => $coach->meeting_url,
                ]);
            } catch (UniqueConstraintViolationException $e) {
                throw new MeetingNoAvailableCoachException($e);
            }

            $coach->notify(
                new MeetingReservationNotification($meeting)
            );

            $student->notify(
                new MeetingReservationNotification($meeting)
            );

            $transaction = ($this->consumeAction)(
                $student,
                $meeting->id
            );

            $meeting->update([
                'meeting_quota_transaction_id' => $transaction->id,
            ]);

            return $meeting->fresh();
        });

        $coach = $meeting->coach;

        if ($this->googleCalendarService->isConnected((string) $coach->id)) {
            $googleCalendarEventId =
                $this->googleCalendarService->createEvent($meeting);

            $meeting->update([
                'google_calendar_event_id' => $googleCalendarEventId,
            ]);
        }

        return $meeting->fresh();
    }

    /**
     * 指定された資格・日時に予約可能なコーチを取得する。
     *
     * @return Collection<int, User>
     */
    private function findAvailableCoaches(
        Certification $certification,
        Carbon $scheduledAt
    ): Collection {
        // ここに元Controllerの
        // findAvailableCoaches() の検索ロジックを移す
    }
}

