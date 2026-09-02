<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CertificationStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\QaThread;
use App\Models\User;

class QaThreadPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct() {}

    public function viewAny(User $user): bool
    {
        // 管理者は常にOK
        if ($user->role === UserRole::Admin) {
            return true;
        }

        // コーチ・受講生は受講中のみOK
        if (
            in_array($user->role, [UserRole::Student, UserRole::Coach], true)
            && $user->status === UserStatus::InProgress
        ) {
            return true;
        }

        return false;

    }

    public function view(User $user, QaThread $thread): bool
    {
        // 管理者は常にOK
        if ($user->role === UserRole::Admin) {
            return true;
        }

        // 公開停止中の資格は管理者以外NG
        if ($thread->certification->status !== CertificationStatus::Published) {
            return false;
        }

        // 受講生は公開済資格なら閲覧可能
        if ($user->role === UserRole::Student) {
            if ($user->status !== UserStatus::InProgress) {
                return false;
            }

            return true;
        }

        // コーチは担当資格のみ
        if ($user->role === UserRole::Coach) {
            return in_array(
                $thread->certification_id,
                $user->coachingCertificationIds(),
                true
            );
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
