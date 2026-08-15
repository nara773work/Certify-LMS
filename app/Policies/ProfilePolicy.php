<?php

namespace App\Policies;

use App\Models\User;

class ProfilePolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function update(User $user, User $targetUser): bool
    {
        return $user->id === $targetUser->id;
    }

    public function avatar(User $user, User $targetUser): bool
    {
        return $user->id === $targetUser->id;
    }

    public function avatardelete(User $user, User $targetUser): bool
    {
        return $user->id === $targetUser->id;
    }

    public function updatepassword(User $user, User $targetUser): bool
    {
        return $user->id === $targetUser->id;
    }
}
