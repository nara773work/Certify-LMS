<?php

declare(strict_types=1);

namespace App\UseCases\Meeting;

use App\Models\Meeting;
use App\Models\User;
use App\Models\Enrollment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CreateAction{

    public function __invoke(Enrollment $enrollment): Enrollment{
        return $enrollment->loadMissing('certification');
    }
}