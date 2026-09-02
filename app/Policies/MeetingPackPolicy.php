<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\MeetingPack;
use App\Models\User;

class MeetingPackPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct() {}

    public function view(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function create(User $user, MeetingPack $plan): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, MeetingPack $plan): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, MeetingPack $plan): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function publish(User $user, MeetingPack $plan): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function archive(User $user, MeetingPack $plan): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function unarchive(User $user, MeetingPack $plan): bool
    {
        return $user->role === UserRole::Admin;
    }
}
