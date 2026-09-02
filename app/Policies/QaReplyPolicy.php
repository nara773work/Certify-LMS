<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\QaReply;
use App\Models\User;

class QaReplyPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Coach
            || $user->role === UserRole::Student;
    }

    public function edit(User $user, QaReply $reply): bool
    {
        return $user->id === $reply->user_id;
    }

    public function update(User $user, QaReply $reply): bool
    {
        return $user->id === $reply->user_id;
    }

    public function delete(User $user, QaReply $reply): bool
    {
        return $user->role === UserRole::Admin
            || $user->id === $reply->user_id;
    }
}
