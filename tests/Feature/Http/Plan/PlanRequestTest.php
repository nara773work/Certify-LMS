<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Plan;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanRequestTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    public function test_name_required(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => '',
            'description' => null,
            'duration_days' => 10,
            'default_meeting_quota' => 10,
            'sort_order' => 10,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/plans', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'name',
        ]);
    }

    public function test_name_100(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => str_repeat('あ', 100),
            'description' => null,
            'duration_days' => 10,
            'default_meeting_quota' => 10,
            'sort_order' => 10,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/plans', $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('plans', [
            'name' => str_repeat('あ', 100),
        ]);
    }

    public function test_name_101(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => str_repeat('あ', 101),
            'description' => null,
            'duration_days' => 10,
            'default_meeting_quota' => 10,
            'sort_order' => 10,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/plans', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'name',
        ]);
    }

    public function test_description_2000(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => str_repeat('あ', 2000),
            'duration_days' => 10,
            'default_meeting_quota' => 10,
            'sort_order' => 10,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/plans', $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('plans', [
            'description' => str_repeat('あ', 2000),
        ]);

    }

    public function test_duration_days_required(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => str_repeat('あ', 2000),
            'duration_days' => '',
            'default_meeting_quota' => 10,
            'sort_order' => 10,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/plans', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'duration_days',
        ]);
    }

    public function test_duration_days_3650(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => null,
            'duration_days' => 3650,
            'default_meeting_quota' => 10,
            'sort_order' => 10,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/plans', $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('plans', [
            'duration_days' => 3650,
        ]);
    }

    public function test_duration_days_3651(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => null,
            'duration_days' => str_repeat('あ', 3651),
            'default_meeting_quota' => 10,
            'sort_order' => 10,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/plans', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'duration_days',
        ]);
    }

    public function test_duration_days_1(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => null,
            'duration_days' => 1,
            'default_meeting_quota' => 10,
            'sort_order' => 10,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/plans', $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('plans', [
            'duration_days' => 1,
        ]);
    }

    public function test_duration_days_0(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => null,
            'duration_days' => 0,
            'default_meeting_quota' => 10,
            'sort_order' => 10,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/plans', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'duration_days',
        ]);
    }

    public function test_default_meeting_quota_required(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => null,
            'duration_days' => 10,
            'default_meeting_quota' => '',
            'sort_order' => 10,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/plans', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'default_meeting_quota',
        ]);

    }

    public function test_default_meeting_quota_1000(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => null,
            'duration_days' => 10,
            'default_meeting_quota' => 1000,
            'sort_order' => 10,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/plans', $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('plans', [
            'default_meeting_quota' => 1000,
        ]);
    }

    public function test_default_meeting_quota_1001(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => null,
            'duration_days' => 10,
            'default_meeting_quota' => 1001,
            'sort_order' => 10,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/plans', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'default_meeting_quota',
        ]);
    }

    public function test_default_meeting_quota_0(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => null,
            'duration_days' => 10,
            'default_meeting_quota' => 0,
            'sort_order' => 10,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/plans', $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('plans', [
            'default_meeting_quota' => 0,
        ]);
    }

    public function test_default_meeting_quota_minus1(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => '',
            'duration_days' => 10,
            'default_meeting_quota' => -1,
            'sort_order' => 10,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/plans', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'default_meeting_quota',
        ]);
    }

    public function test_sort_1000(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => null,
            'duration_days' => 10,
            'default_meeting_quota' => 10,
            'sort_order' => str_repeat('あ', 1000),
        ];

        $response = $this->actingAs($user)
            ->post('/admin/plans', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'sort_order',
        ]);
    }

    public function test_sort_1001(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => null,
            'duration_days' => 10,
            'default_meeting_quota' => 10,
            'sort_order' => str_repeat('あ', 1001),
        ];

        $response = $this->actingAs($user)
            ->post('/admin/plans', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'sort_order',
        ]);
    }

    public function test_sort_0(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => null,
            'duration_days' => 10,
            'default_meeting_quota' => 10,
            'sort_order' => 0,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/plans', $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('plans', [
            'sort_order' => 0,
        ]);
    }

    public function test_sort_minus1(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => null,
            'duration_days' => 10,
            'default_meeting_quota' => 10,
            'sort_order' => -1,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/plans', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'sort_order',
        ]);
    }
}
