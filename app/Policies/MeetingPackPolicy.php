<?php

namespace App\Policies;

use App\Models\User;
use App\Enums\UserRole;
use App\Models\MeetingPack;

class MeetingPackPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
    }
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

