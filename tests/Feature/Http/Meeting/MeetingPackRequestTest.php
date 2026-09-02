<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Meeting;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingPackRequestTest extends TestCase
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
            'meeting_count' => 1,
            'price' => 1000,
            'stripe_price_id' => 1000,
            'sort_order' => 0,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/meeting-packs', $data);

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
            'meeting_count' => 1,
            'price' => 1000,
            'stripe_price_id' => null,
            'sort_order' => 0,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/meeting-packs', $data);

        $response->assertStatus(302);

        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('meeting_packs', [
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
            'meeting_count' => 1,
            'price' => 1000,
            'stripe_price_id' => null,
            'sort_order' => 0,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/meeting-packs', $data);

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
            'meeting_count' => 1,
            'price' => 1000,
            'stripe_price_id' => null,
            'sort_order' => 0,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/meeting-packs', $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('meeting_packs', [
            'description' => str_repeat('あ', 2000),
        ]);

    }

    public function test_description_2001(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => str_repeat('あ', 2001),
            'meeting_count' => 1,
            'price' => 1000,
            'stripe_price_id' => 1000,
            'sort_order' => 0,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/meeting-packs', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'description',
        ]);

    }

    public function test_meeting_count_required(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => '',
            'meeting_count' => '',
            'price' => 1000,
            'stripe_price_id' => null,
            'sort_order' => 0,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/meeting-packs', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'meeting_count',
        ]);
    }

    public function test_meeting_count_100(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => null,
            'meeting_count' => 100,
            'price' => 1000,
            'stripe_price_id' => null,
            'sort_order' => 0,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/meeting-packs', $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('meeting_packs', [
            'meeting_count' => 100,
        ]);
    }

    public function test_meeting_count_101(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => null,
            'meeting_count' => 101,
            'price' => 1000,
            'stripe_price_id' => null,
            'sort_order' => 0,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/meeting-packs', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'meeting_count',
        ]);
    }

    public function test_meeting_count_1(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => null,
            'meeting_count' => 1,
            'price' => 1000,
            'stripe_price_id' => null,
            'sort_order' => 0,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/meeting-packs', $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('meeting_packs', [
            'meeting_count' => 1,
        ]);
    }

    public function test_meeting_count_0(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => null,
            'meeting_count' => 0,
            'price' => 1000,
            'stripe_price_id' => null,
            'sort_order' => 0,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/meeting-packs', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'meeting_count',
        ]);
    }

    public function test_default_price_required(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => null,
            'meeting_count' => 1,
            'price' => '',
            'stripe_price_id' => null,
            'sort_order' => 0,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/meeting-packs', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'price',
        ]);

    }

    public function test_default_price_1000000(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => null,
            'meeting_count' => 1,
            'price' => 1000000,
            'stripe_price_id' => null,
            'sort_order' => 0,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/meeting-packs', $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('meeting_packs', [
            'price' => 1000000,
        ]);
    }

    public function test_default_price_1000001(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => null,
            'meeting_count' => 1,
            'price' => 1000001,
            'stripe_price_id' => null,
            'sort_order' => 0,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/meeting-packs', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'price',
        ]);
    }

    public function test_price_0(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => null,
            'meeting_count' => 1,
            'price' => 0,
            'stripe_price_id' => null,
            'sort_order' => 0,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/meeting-packs', $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('meeting_packs', [
            'price' => 0,
        ]);
    }

    public function test_price_minus1(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => null,
            'meeting_count' => 1,
            'price' => -1,
            'stripe_price_id' => null,
            'sort_order' => 0,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/meeting-packs', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'price',
        ]);
    }

    public function test_stripe_price_id_255(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => null,
            'meeting_count' => 1,
            'price' => 1000,
            'stripe_price_id' => str_repeat('A', 255),
            'sort_order' => 0,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/meeting-packs', $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('meeting_packs', [
            'stripe_price_id' => str_repeat('A', 255),
        ]);
    }

    public function test_stripe_price_id_256(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => null,
            'meeting_count' => 1,
            'price' => 1000,
            'stripe_price_id' => str_repeat('あ', 256),
            'sort_order' => 0,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/meeting-packs', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'stripe_price_id',
        ]);
    }

    public function test_sort_1000(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => null,
            'meeting_count' => 1,
            'price' => 1000,
            'stripe_price_id' => null,
            'sort_order' => 1000,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/meeting-packs', $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('meeting_packs', [
            'sort_order' => 1000,
        ]);
    }

    public function test_sort_1001(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => null,
            'meeting_count' => 1,
            'price' => 1000,
            'stripe_price_id' => null,
            'sort_order' => 1001,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/meeting-packs', $data);

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
            'meeting_count' => 1,
            'price' => 1000,
            'stripe_price_id' => null,
            'sort_order' => 0,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/meeting-packs', $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('meeting_packs', [
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
            'meeting_count' => 1,
            'price' => 1000,
            'stripe_price_id' => 1000,
            'sort_order' => -1,
        ];

        $response = $this->actingAs($user)
            ->post('/admin/meeting-packs', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'sort_order',
        ]);
    }
}
