<?php

declare(strict_types=1);

namespace App\Actions\Meeting;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CreateFallbackAction{

    public function __invoke(){
        
    return  $user ?->enrollments()
            ->whereIn('status', [EnrollmentStatus::Learning->value, EnrollmentStatus::Passed->value])
            ->with('certification')
            ->get();
    }
}