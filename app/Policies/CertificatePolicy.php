<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\User;

final class CertificatePolicy
{
    /**
     * 修了証PDFのダウンロードを許可する。
     *
     * admin:
     *   すべて許可
     *
     * coach:
     *   自分が担当している資格の修了証のみ許可
     */
    public function download(User $user, Certificate $certificate): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        if ($user->role !== UserRole::Coach) {
            return false;
        }

        if($user->id === $certificate->user_id) {
            return true;
        }

        return $user->assignedCertifications()
            ->whereKey($certificate->certification_id)
            ->exists();
    }
}