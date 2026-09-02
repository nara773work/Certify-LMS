<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AiChatConversation;
use App\Models\User;

class AiChatPolicy
{
    /**
     * AIチャットを利用できるのは
     * 学習中の受講生のみ。
     */
    public function access(User $user): bool
    {
        return $user->role === UserRole::Student;
    }

    /**
     * 会話を操作できるのは、その会話のオーナーのみ。
     */
    public function owner(User $user, AiChatConversation $conversation): bool
    {
        return $conversation->user_id === $user->id;
    }
}
