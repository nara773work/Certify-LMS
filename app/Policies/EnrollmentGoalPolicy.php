<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Enrollment;
use App\Models\EnrollmentGoal;
use App\Models\User;

class EnrollmentGoalPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        
    }

    public function view(User $user, EnrollmentGoal $goal): bool
    {
        if ($user->role !== UserRole::Coach) 
        { 
            return false; 
        } 
        $enrollment = $goal->enrollment; 

        if ($enrollment === null) { 
            return false; 
        } $certification = $enrollment->certification; 

        if ($certification === null) {
             return false; 
        } 

        return $certification->coaches()->where('users.id', $user->id)->exists();
    }

        public function create(User $user,Enrollment $enrollment): bool
    {
        return $user->role === UserRole::Student 
            && $user->id === $enrollment->user_id;
    }

        public function update(User $user, EnrollmentGoal $goal): bool
    {
        return $user->id === $goal->user_id;
    }

        public function delete(User $user, EnrollmentGoal $goal): bool
    {
        return $user->id === $goal->user_id;
    }

        public function markAchieved(User $user, EnrollmentGoal $goal): bool
    {
        return $user->id === $goal->user_id;
    }

        public function unmarkAchieved(User $user, EnrollmentGoal $goal): bool
    {
        return $user->id === $goal->user_id;
    }
}
