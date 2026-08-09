<?php

namespace App\Policies;

use App\Models\User;
use App\Enums\UserRole;
use App\Models\Plan;

class PlanPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }
    public function view(User $user,Plan $plan): bool
    {
        return $user->role === UserRole::Admin;
    }
        public function create(User $user, Plan $plan): bool
    {
        return $user->role === UserRole::Admin;
    }
        public function update(User $user, Plan $plan): bool
    {
        return $user->role === UserRole::Admin;
    }
        public function delete(User $user, Plan $plan): bool
    {
        return $user->role === UserRole::Admin;
    }
        public function publish(User $user, Plan $plan): bool
    {
        return $user->role === UserRole::Admin;
    }
        public function archive(User $user, Plan $plan): bool
    {
        return $user->role === UserRole::Admin;
    }
    
        public function unarchive(User $user, Plan $plan): bool
    {
        return $user->role === UserRole::Admin;
    }
}
