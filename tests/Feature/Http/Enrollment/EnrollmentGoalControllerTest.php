<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Enrollment;

use App\Enums\UserRole;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentGoalControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    public function test_enrollment_goal_controller_store_success(): void
    {
        $this->seed();

        $user = User::query()
            ->where('role', UserRole::Student->value)->firstOrFail();
        $enrollment = $user->enrollments()->first();

        $goal = [
            'title' => 'test',
            'enrollment_id' => $enrollment->id,
            'description' => null,
            'target_date' => Carbon::today()->addDays(3),
        ];
        $response = $this->actingAs($user)
            ->post("/enrollments/{$enrollment->id}/goals", $goal);

        $response->assertStatus(302);

        $this->assertDatabaseHas('enrollment_goals',
            ['title' => 'test',
                'enrollment_id' => $enrollment->id,
                'description' => null,
                'target_date' => Carbon::today()->addDays(3)->format('Y-m-d'),
                'user_id' => $user->id,
            ]);
    }

    public function test_enrollment_goal_controller_store_403(): void
    {
        $this->seed();

        $user = User::query()
            ->where('role', UserRole::Student->value)->first();
        $enrollment = $user->enrollments->first();

        $otheruser = User::query()
            ->where('role', UserRole::Student->value)
            ->where('id', '!=', $user->id)
            ->firstOrFail();

        $goal = [
            'title' => 'test',
            'enrollment_id' => $enrollment->id,
            'description' => null,
            'target_date' => Carbon::today()->addDays(3),
            'user_id' => $user->id,
        ];
        $response = $this->actingAs($otheruser)
            ->post("/enrollments/{$enrollment->id}/goals", $goal);

        $response->assertStatus(403);
    }

    public function test_enrollment_goal_controller_edit(): void
    {

        $this->seed();

        $user = User::query()
            ->where('role', UserRole::Student->value)
            ->firstOrFail();

        $goal = $user->enrollments()
            ->firstOrFail()
            ->goals()
            ->firstOrFail();

        $response = $this->actingAs($user)
            ->get("/enrollment-goals/{$goal->id}/edit");
        $response->assertStatus(200);

    }

    public function test_enrollment_goal_controller_show_403(): void
    {
        $this->seed();

        $user = User::query()
            ->where('role', UserRole::Student->value)->firstOrFail();

        $goal = $user->enrollments()->firstOrFail()->goals()->first();

        $otheruser = User::query()
            ->where('role', UserRole::Student->value)
            ->where('id', '!=', $user->id)
            ->firstOrFail();

        $response = $this->actingAs($otheruser)
            ->get("/enrollment-goals/{$goal->id}/edit");

        $response->assertStatus(403);
    }

    public function test_enrollment_goal_controller_update_success(): void
    {
        $this->seed();

        $user = User::query()
            ->where('role', UserRole::Student->value)->firstOrFail();
        $goal = $user->enrollments()
            ->firstOrFail()
            ->goals()
            ->firstOrFail();

        $data = [
            'title' => '更新した目標',
            'target_date' => Carbon::today()->addDays(5)->format('Y-m-d'),
            'description' => '更新した説明',
        ];

        $response = $this->actingAs($user)
            ->patch("/enrollment-goals/{$goal->id}", $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('enrollment_goals',
            ['id' => $goal->id,
                'title' => '更新した目標',
                'description' => '更新した説明',
            ]);
    }

    public function test_enrollment_goal_controller_update_403(): void
    {
        $this->seed();
        $user = User::query()
            ->where('role', UserRole::Student->value)
            ->firstOrFail();

        $goal = $user->enrollments()
            ->firstOrFail()
            ->goals()
            ->firstOrFail();

        $otherUser = User::query()
            ->where('role', UserRole::Student->value)
            ->where('id', '!=', $user->id)
            ->firstOrFail();

        $data = [
            'title' => '不正な更新',
            'target_date' => Carbon::today()->addDays(5)->format('Y-m-d'),
            'description' => '不正な更新', ];

        $response = $this->actingAs($otherUser)
            ->patch("/enrollment-goals/{$goal->id}", $data);

        $response->assertStatus(403);
    }

    public function test_enrollment_goal_controller_delete_success(): void
    {
        $this->seed();

        $user = User::query()
            ->where('role', UserRole::Student->value)->firstOrFail();
        $goal = $user->enrollments()
            ->firstOrFail()
            ->goals()
            ->firstOrFail();

        $response = $this->actingAs($user)
            ->delete("/enrollment-goals/{$goal->id}");

        $response->assertStatus(302);

        $this->assertDatabasemissing('enrollment_goals',
            ['id' => $goal->id,
            ]);
    }

    public function test_enrollment_goal_controller_delete_403(): void
    {
        $this->seed();
        $user = User::query()
            ->where('role', UserRole::Student->value)
            ->firstOrFail();

        $goal = $user->enrollments()
            ->firstOrFail()
            ->goals()
            ->firstOrFail();

        $otherUser = User::query()
            ->where('role', UserRole::Student->value)
            ->where('id', '!=', $user->id)
            ->firstOrFail();

        $response = $this->actingAs($otherUser)
            ->delete("/enrollment-goals/{$goal->id}");

        $response->assertStatus(403);
    }

    public function test_enrollment_goal_controller_achieve_success(): void
    {
        $this->seed();

        $user = User::query()
            ->where('role', UserRole::Student->value)->firstOrFail();

        $goal = $user->enrollments()
            ->firstOrFail()
            ->goals()
            ->whereNull('achieved_at')
            ->firstOrFail();

        $data = [
            'achieved_at' => now(),
        ];

        $response = $this->actingAs($user)
            ->post("/enrollment-goals/{$goal->id}/achieve", $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('enrollment_goals',
            ['id' => $goal->id,
                'achieved_at' => now(),
            ]);
    }

    public function test_enrollment_goal_controller_achieve_403(): void
    {
        $this->seed();
        $user = User::query()
            ->where('role', UserRole::Student->value)
            ->firstOrFail();

        $goal = $user->enrollments()
            ->firstOrFail()
            ->goals()
            ->whereNull('achieved_at')
            ->firstOrFail();

        $otherUser = User::query()
            ->where('role', UserRole::Student->value)
            ->where('id', '!=', $user->id)
            ->firstOrFail();

        $data = [
            'ahcieved_at' => null,
        ];

        $response = $this->actingAs($otherUser)
            ->delete("/enrollment-goals/{$goal->id}/achieve", $data);

        $response->assertStatus(403);

    }
}
