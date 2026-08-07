<?php

namespace App\Policies;

use App\Models\User;
use App\Models\QaThread;
use App\Enums\UserRole;
use App\Enums\CertificationStatus;
use App\Enums\UserStatus;

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

    if (
    $user->role !== UserRole::Admin &&
    $user->status !== UserStatus::InProgress
    ) {
        return false;
    }

    }

    public function view(User $user, QaThread $thread): bool
{
        // 管理者は全て閲覧可能
    if ($user->role === UserRole::Admin) {
        return true;
    }

    // 公開停止中の資格は管理者以外閲覧不可
    if ($thread->certification->status !== CertificationStatus::Published) {
        return false;
    }

    // 受講生は公開済資格なら閲覧可能
    if ($user->role === UserRole::Student) {
        return true;
    }

    // コーチは担当資格のみ閲覧可能
    if ($user->role === UserRole::Coach) {
        return in_array(
            $thread->certification_id,
            $user->coachingCertificationIds(),
            true
        );
    }

    if (
    $user->role !== UserRole::Admin &&
    $user->status !== UserStatus::InProgress
    ) {
        return false;
    }
        return false;
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
