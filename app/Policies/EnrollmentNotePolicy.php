<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Enrollment;
use App\Models\EnrollmentNote;
use App\Models\User;

class EnrollmentNotePolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function viewAny(User $user, Enrollment $enrollment): bool
    {
        return $user->role === UserRole::Admin
            || $user->assignedCertifications()
                ->where('certifications.id', $enrollment->certification_id)
                ->exists();
    }

    public function create(User $user, Enrollment $enrollment): bool
    {
        return $user->role === UserRole::Admin
            || $user->assignedCertifications()
                ->where('certifications.id', $enrollment->certification_id)
                ->exists();
    }

    public function edit(User $user, EnrollmentNote $note): bool
    {
        return $user->id === $note->user_id
            || $user->role === UserRole::Admin;
    }

    public function update(User $user, EnrollmentNote $note): bool
    {
        return $user->id === $note->user_id
            || $user->role === UserRole::Admin;
    }

    public function delete(User $user, EnrollmentNote $note): bool
    {
        return $user->id === $note->user_id
            || $user->role === UserRole::Admin;
    }
}
