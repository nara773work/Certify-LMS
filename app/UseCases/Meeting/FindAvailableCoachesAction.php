<?php

declare(strict_types=1);

namespace App\Actions\Meeting;

use App\Enums\MeetingStatus;
use App\Models\Certification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FindAvailableAction
{
    public function __invoke(
        Certification $certification,
        Carbon $scheduledAt,
    ): Collection {
        $time = $scheduledAt->format('H:i:s');

        return $certification->coaches()
            ->whereHas('coachAvailabilities', function ($q) use (
                $scheduledAt,
                $time
            ) {
                $q->where('day_of_week', $scheduledAt->dayOfWeek)
                    ->where('is_active', true)
                    ->where('start_time', '<=', $time)
                    ->where('end_time', '>', $time);
            })
            ->whereDoesntHave('meetingsAsCoach', function ($q) use (
                $scheduledAt
            ) {
                $q->where('scheduled_at', $scheduledAt)
                    ->whereIn('status', [
                        MeetingStatus::Reserved->value,
                        MeetingStatus::Completed->value,
                    ]);
            })
            ->get();
    }
}

