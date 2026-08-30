<?php

declare(strict_types=1);

namespace App\UseCases\Meeting;

use App\Enums\MeetingStatus;
use App\Exceptions\InsufficientMeetingQuotaException;
use App\Exceptions\Mentoring\MeetingNoAvailableCoachException;
use App\Models\Certification;
use App\Models\Enrollment;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\MeetingReservationNotification;
use App\Services\GoogleCalendarService;
use App\Services\MeetingAvailabilityService;
use App\Services\MeetingQuotaService;
use App\UseCases\MeetingQuota\ConsumeQuotaAction;
use App\Services\CoachMeetingLoadService;
use Illuminate\Support\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StoreAction
{
    public function __construct(
        private MeetingAvailabilityService $availabilityService,
        private MeetingQuotaService $quotaService,
        private ConsumeQuotaAction $consumeAction,
        private GoogleCalendarService $googleCalendarService,
        private CoachMeetingLoadService $coachLoadService,
    ) {
    }

    public function __invoke(
        Enrollment $enrollment,
        User $student,
        array $validated,
    ): Meeting {
        $scheduledAt = Carbon::parse($validated['scheduled_at']);
        $topic = $validated['topic'] ?? null;

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
                $scheduledAt,
            );

            $candidates = $this->findAvailableCoaches(
                $enrollment->certification,
                $scheduledAt,
            );

            if ($candidates->isEmpty()) {
                throw new MeetingNoAvailableCoachException;
            }

            $coach = $this->coachLoadService
                ->leastLoadedCoach($candidates);

            if (! $coach) {
                throw new MeetingNoAvailableCoachException;
            }

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

                        if ($this->googleCalendarService->isConnected(
            (string) $meeting->coach_id
        )) {
            $eventId = $this->googleCalendarService->createEvent($meeting);

            if ($eventId !== null) {
                $meeting->update([
                    'google_calendar_event_id' => $eventId,
                ]);
            }
        }
          
            } catch (UniqueConstraintViolationException $e) {
                throw new MeetingNoAvailableCoachException($e);
            }

            $transaction = ($this->consumeAction)(
                $student,
                $meeting->id,
            );

            $meeting->update([
                'meeting_quota_transaction_id' => $transaction->id,
            ]);

            $coach->notify(
                new MeetingReservationNotification($meeting)
            );

            $student->notify(
                new MeetingReservationNotification($meeting)
            );

            return $meeting->fresh();
        });

        return $meeting->fresh();
    }

    /**
     * 指定された資格・日時に予約可能なコーチを取得する。
     *
     * @return Collection<int, User>
     */
    private function findAvailableCoaches(
        Certification $certification,
        Carbon $scheduledAt,
    ): Collection {
        $time = $scheduledAt->format('H:i:s');

        return $certification->coaches()
            ->whereHas('coachAvailabilities', function ($q) use ($scheduledAt, $time) {
                $q->where('day_of_week', $scheduledAt->dayOfWeek)
                    ->where('is_active', true)
                    ->where('start_time', '<=', $time)
                    ->where('end_time', '>', $time);
            })
            ->whereDoesntHave('meetingsAsCoach', function ($q) use ($scheduledAt) {
                $q->where('scheduled_at', $scheduledAt)
                    ->whereIn('status', [
                        MeetingStatus::Reserved->value,
                        MeetingStatus::Completed->value,
                    ]);
            })
            ->get();
    }
}