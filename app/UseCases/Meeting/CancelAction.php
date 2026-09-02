<?php

declare(strict_types=1);

namespace App\UseCases\Meeting;

use App\Models\Meeting;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\UseCases\MeetingQuota\RefundQuotaAction;
use Illuminate\Support\Facades\DB;

class CancelAction
{
    public function __construct(
        private RefundQuotaAction $refundAction,
        private GoogleCalendarService $googleCalendarService,
    ) {}

    public function __invoke(
        Meeting $meeting,
        User $actor,
    ): void {
        DB::transaction(function () use ($meeting, $actor) {
            $meeting->update([
                'status' => 'canceled',
                'canceled_by_user_id' => $actor->id,
                'canceled_at' => now(),
            ]);

            ($this->refundAction)(
                $meeting->student,
                $meeting->id
            );
        });

        // Google Calendarから削除
        if ($meeting->google_calendar_event_id !== null) {
            $this->googleCalendarService->deleteEvent($meeting);
        }
    }
}
