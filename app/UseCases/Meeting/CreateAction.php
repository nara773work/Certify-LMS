<?php

declare(strict_types=1);

namespace App\UseCases\Meeting;

use App\Models\Enrollment;

class CreateAction
{
    public function __invoke(Enrollment $enrollment): Enrollment
    {
        return $enrollment->loadMissing('certification');
    }
}
