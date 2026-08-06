<?php

namespace App\Policies;

use App\Models\User;
use App\Models\QaThread;
use App\Models\QaReply; 
use App\Enums\UserRole;

class QaReplyPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function create(User $user, QaThread $thread): bool
    {
        return $user->role === UserRole::Coach
            || $user->role === UserRole::Student;
    }

    public function update(User $user, QaReply $reply): bool
    {
        return $user->role === UserRole::Coach
            || $user->role === UserRole::Student;
    }

    public function delete(User $user, QaReply $reply): bool
    {
        return $user->role === UserRole::Admin
            || $user->role === UserRole::Coach
            || $user->role === UserRole::Student;
    }
}
