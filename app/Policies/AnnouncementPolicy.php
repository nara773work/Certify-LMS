<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;

class AnnouncementPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function view(User $user, Announcement $announcement): bool
    {
        return $announcement->users()
            ->where('users.id', $user->id)
            ->exists();
    }
}
