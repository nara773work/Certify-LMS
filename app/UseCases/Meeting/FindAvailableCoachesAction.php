<?php

declare(strict_types=1);

namespace App\Actions\Meeting;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FindeAvailableAction{

    public function __invoke(){
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