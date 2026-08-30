<?php

declare(strict_types=1);

namespace App\Actions\Meeting;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CreateFallbackAction{

    public function __invoke(User $user): Collection{
        
    return  $user?->enrollments()
            ->whereIn('status', [EnrollmentStatus::Learning->value, EnrollmentStatus::Passed->value])
            ->with('certification')
            ->get();
    }
}