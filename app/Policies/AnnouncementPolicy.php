<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Announcement;

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
