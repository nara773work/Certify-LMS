<?php

namespace App\Policies;

use App\Models\User;
use App\Models\QaThread;
use App\Enums\UserRole;

class QaThreadPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            UserRole::Admin,
            UserRole::Student,
            UserRole::Coach,
        ]);
    }

    public function view(User $user, QaThread $thread): bool
    {
        return $user->id === $thread->user_id;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Student;
    }

    public function edit(User $user, QaThread $thread): bool
    {
        return $user->id === $thread->user_id;
    }

    public function update(User $user, QaThread $thread): bool
    {
        return $user->id === $thread->user_id;
    }

    public function delete(User $user, QaThread $thread): bool
    {
        return $user->role === UserRole::Admin
            || $user->id === $thread->user_id;
    }

    public function resolve(User $user, QaThread $thread): bool
    {
        return $user->id === $thread->user_id;
    }

    public function unresolve(User $user, QaThread $thread): bool
    {
        return $user->id === $thread->user_id;
    }
}
