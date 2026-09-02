<?php

declare(strict_types=1);

namespace App\UseCases\Meeting;

use App\Enums\MeetingStatus;
use App\Exceptions\Mentoring\MeetingStatusTransitionException;
use App\Models\Meeting;
use App\Models\MeetingMemo;

class UpsertMemoAction
{
    public function __invoke(
        Meeting $meeting,
        string $body,
    ): MeetingMemo {
        if ($meeting->status === MeetingStatus::Canceled) {
            throw MeetingStatusTransitionException::forMemo();
        }

        return MeetingMemo::updateOrCreate(
            [
                'meeting_id' => $meeting->id,
            ],
            [
                'body' => $body,
            ],
        );
    }
}
