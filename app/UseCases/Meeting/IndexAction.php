<?php

declare(strict_types=1);

namespace App\UseCases\Meeting;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexAction{

    public function __invoke(User $user,string $filter = 'upcoming'): LengthAwarePaginator{

        $query = Meeting::query()
            ->with(['enrollment.certification', 'coach'])
            ->forStudent($user)
            ->orderByDesc('scheduled_at');

        return match ($filter) {
            'past' => $query->past()->paginate(20),
            'all' => $query->paginate(20),
            default => $query->upcoming()->paginate(20),
        };
    }
}
