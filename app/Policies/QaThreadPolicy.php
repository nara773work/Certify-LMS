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

    public function view(User $user, QaThread $qathread): bool
    {
        return $user->role === UserRole::Admin
            || $user->id === UserRole::Student
            || $user->id === UserRole::Coach;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Student;
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
