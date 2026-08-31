<?php

namespace Tests\Feature\Http\Enrollment;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use App\Enums\UserRole;
use App\Models\User;

class EnrollmentGoalRequestTest extends TestCase
{
    /**
     * A basic feature test example.
     */

    use RefreshDatabase;

    public function test_title_required(): void
    {
        $this->seed();

        $user = User::query()
        ->where('role', UserRole::Student->value)->firstOrFail();
        $enrollment = $user->enrollments()->first();

        $goal = [
            'title' => '',
            'enrollment_id' => $enrollment->id,
            'description' => null,
            'target_date' => Carbon::today()->addDays(3),
        ];
        $response = $this->actingAs($user)
        ->post("/enrollments/{$enrollment->id}/goals",$goal);

        $response->assertSessionHasErrors([
            'title',
        ]);

        $this->assertDatabaseMissing('enrollment_goals', [
            'title' => '',
            'enrollment_id' => $enrollment->id,
        ]);

    }

        public function test_title_100(): void
    {
        $this->seed();

        $user = User::query()
        ->where('role', UserRole::Student->value)->firstOrFail();
        $enrollment = $user->enrollments()->first();

        $goal = [
            'title' =>str_repeat('あ', 100),
            'enrollment_id' => $enrollment->id,
            'description' => null,
            'target_date' => Carbon::today()->addDays(3),
        ];
        $response = $this->actingAs($user)
        ->post("/enrollments/{$enrollment->id}/goals",$goal);

        $this->assertDatabaseHas('enrollment_goals', [
            'title' =>str_repeat('あ', 100),
            'enrollment_id' => $enrollment->id,
        ]);
    }

        public function test_title_101(): void
    {
        $this->seed();

        $user = User::query()
        ->where('role', UserRole::Student->value)->firstOrFail();
        $enrollment = $user->enrollments()->first();

        $goal = [
            'title' => str_repeat('あ', 101),
            'enrollment_id' => $enrollment->id,
            'description' => null,
            'target_date' => Carbon::today()->addDays(3),
        ];
        $response = $this->actingAs($user)
        ->post("/enrollments/{$enrollment->id}/goals",$goal);

        $response->assertSessionHasErrors([
            'title',
        ]);

        $this->assertDatabaseMissing('enrollment_goals', [
            'title' => str_repeat('あ', 101),
            'enrollment_id' => $enrollment->id,
        ]);
    }

        public function test_description_1000(): void
    {
        $this->seed();

        $user = User::query()
        ->where('role', UserRole::Student->value)->firstOrFail();
        $enrollment = $user->enrollments()->first();

        $goal = [
            'title' =>'title',
            'enrollment_id' => $enrollment->id,
            'description' => str_repeat('あ', 1000),
            'target_date' => Carbon::today()->addDays(3),
        ];
        $response = $this->actingAs($user)
        ->post("/enrollments/{$enrollment->id}/goals",$goal);

        $this->assertDatabaseHas('enrollment_goals', [
            'description' => str_repeat('あ', 1000),
            'enrollment_id' => $enrollment->id,
        ]);
    }

        public function test_description_1001(): void
    {
        $this->seed();

        $user = User::query()
        ->where('role', UserRole::Student->value)->firstOrFail();
        $enrollment = $user->enrollments()->first();

        $goal = [
            'title' => 'test',
            'enrollment_id' => $enrollment->id,
            'description' => str_repeat('あ', 1001),
            'target_date' => Carbon::today()->addDays(3),
        ];
        $response = $this->actingAs($user)
        ->post("/enrollments/{$enrollment->id}/goals",$goal);

        $response->assertSessionHasErrors([
            'description'
        ]);

        $this->assertDatabaseMissing('enrollment_goals', [
            'description' => str_repeat('あ', 1001),
            'enrollment_id' => $enrollment->id,
        ]);
    }

        public function test_target_date_after(): void
    {
        $this->seed();

        $user = User::query()
        ->where('role', UserRole::Student->value)->firstOrFail();
        $enrollment = $user->enrollments()->first();

        $goal = [
            'title' => 'test',
            'enrollment_id' => $enrollment->id,
            'description' => null,
            'target_date' => Carbon::yesterday(),
        ];
        $response = $this->actingAs($user)
        ->post("/enrollments/{$enrollment->id}/goals",$goal);

        $response->assertSessionHasErrors([
            'target_date' ,
        ]);

        $this->assertDatabaseMissing('enrollment_goals', [
            'target_date' => Carbon::yesterday(),
            'enrollment_id' => $enrollment->id,
        ]);
    }
}
