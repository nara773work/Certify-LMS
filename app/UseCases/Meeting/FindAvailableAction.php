<?php

declare(strict_types=1);

namespace App\UseCases\Meeting;

use App\Models\Enrollment;
use App\Services\MeetingAvailabilityService;
use Illuminate\Support\Carbon;

class FindAvailableAction
{
    public function __construct(
        private MeetingAvailabilityService $availabilityService,
    ) {
    }

    public function __invoke(
        Enrollment $enrollment,
        string $date,
    ) {
        $date = Carbon::parse($date);

        return $this->availabilityService->slotsForCertification(
            $enrollment->loadMissing('certification')->certification,
            $date,
        );
    }
}