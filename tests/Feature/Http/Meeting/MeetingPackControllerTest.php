<?php

namespace Tests\Feature\Http\Meeting;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\MeetingPack;
use App\Enums\UserRole;
use App\Enums\MeetingPackStatus;

class MeetingPackControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_MeetingPackController_index_student(): void
    {
        $this->seed();

        $user = User::where('role',UserRole::Student)->first();

        $response = $this->actingAs($user)->get('/admin/meeting-packs');
        $response->assertStatus(403);

    }

        public function test_MeetingPackController_index_coach(): void
    {
        $this->seed();

        $user = User::where('role',UserRole::Coach)->first();

        $response = $this->actingAs($user)->get('/admin/meeting-packs');
        $response->assertStatus(403);

    }

    public function test_MeetingPackController_index(): void
    {
        $this->seed();

        $user = User::where('role',UserRole::Admin)->first();

        $response = $this->actingAs($user)->get('/admin/meeting-packs');
        $response->assertStatus(200);

    }

    public function test_MeetingPackController_index_keyword(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $keyword = '5';

        $response = $this->actingAs($user)
        ->get('/admin/meeting-packs?&keyword='.$keyword);

        $response->assertStatus(200);

        $response->assertSee('5 回パック');

        $response->assertDontSee('1 回パック');

    }

        public function test_MeetingPackController_index_filter_status(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $response = $this->actingAs($user)
        ->get('/admin/meeting-packs?status='.MeetingPackStatus::Published->value);

        $response->assertStatus(200);

        $response->assertSee('公開中');

        $response->assertDontSee('3 回パック(調整中)');
        $response->assertDontSee('20 回パック(旧)');

    }

    public function test_MeetingPackController_create(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $response = $this->actingAs($user)
        ->get('/admin/meeting-packs/create');

        $response->assertStatus(200);

    }

    /**
     * バリデーションを通過したデータは、DBに保存される
     */
    public function test_MeetingPackController_store_succses(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => 'test',
            'meeting_count' => 3,
            'price' => 2000,
            'stripe_price_id' => null,
            'sort_order' => 100
        ];

        $response = $this->actingAs($user)
        ->post('/admin/meeting-packs',$data);
        

        $response->assertStatus(302);

        $this->assertDatabaseHas('meeting_packs', [
            'name' => 'test',
            'description' => 'test',
            'meeting_count' => 3,
            'price' => 2000,
            'stripe_price_id' => null,
            'sort_order' => 100
        ]);

    }

    /**
     * バリデーションを通過しなかったデータはDBに保存されない
     */
    public function test_MeetingPackController_store_error(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => '',
            'description' => '',
            'meeting_count' => '',
            'price' => '',
            'stripe_price_id' => null,
            'sort_order' => 100
        ];

        $response = $this->actingAs($user)
        ->post('/admin/meeting-packs',$data);

        $response->assertSessionHasErrors([
            'name',
            'meeting_count',
            'price',
        ]);
    }

        public function test_MeetingPackController_show(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();
        $plan = MeetingPack::first();

        $response = $this->actingAs($user)
        ->get("/admin/meeting-packs/{$plan->id}");

        $response->assertStatus(200);

        $response->assertviewIs('meeting-pack.management.show');

        $response->assertSee($plan->name);
        
    }

    public function test_MeetingPackController_edit(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();
        $plan = MeetingPack::first();

        $response = $this->actingAs($user)
        ->get("/admin/meeting-packs/{$plan->id}/edit");

        $response->assertStatus(200);

        $response->assertviewIs('meeting-pack.management.edit');

        $response->assertSee($plan->name);
    
    }

    public function test_MeetingPackController_update(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();
        $plan = MeetingPack::first();

        $update_data = [
            'name' => $plan->name,
            'description' => '',
            'meeting_count' => 3,
            'price' => $plan->price,
            'stripe_price_id' => null,
            'sort_order' => 100
        ];

        $response = $this->actingAs($user)
        ->patch("/admin/meeting-packs/{$plan->id}",$update_data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('meeting_packs', [
            'name' => $plan->name,
            'description' => null,
            'meeting_count' => 3,
            'price' => $plan->price,
            'stripe_price_id' => null,
            'sort_order' => 100
        ]);
    
    }

    /**
     * 投稿者は回答がないスレッドは削除できる
     * DBからも削除される
     */
    public function test_MeetingPackController_delete_success(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();
        $plan = MeetingPack::where('status',MeetingPackStatus::Draft)->first();

        $response = $this->actingAs($user)
            ->delete("/admin/meeting-packs/{$plan->id}");

        $response->assertStatus(302);

        $this->assertDatabaseMissing('meeting_packs', [
            'id' => $plan->id,
        ]);
    }

    public function test_MeetingPackController_delete_error(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();
        $plan = MeetingPack::where('status',MeetingPackStatus::Published)->first();

        $response = $this->actingAs($user)
        ->delete("/admin/meeting-packs/{$plan->id}");

        $response->assertStatus(302);

        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
        ]);
    
    }

    public function test_MeetingPackController_publish(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();
        $plan = MeetingPack::where('status',MeetingPackStatus::Draft)->first();

        $response = $this->actingAs($user)
        ->post("/admin/meeting-packs/{$plan->id}/publish");

        $response->assertStatus(302);

        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'status' => MeetingPackStatus::Published->value,
        ]);
    
    }

    public function test_MeetingPackController_archive(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();
        $plan = MeetingPack::where('status',MeetingPackStatus::Published)->first();

        $response = $this->actingAs($user)
        ->post("/admin/meeting-packs/{$plan->id}/archive");

        $response->assertStatus(302);

        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'status' => MeetingPackStatus::Archived->value,
        ]);
    
    }

    public function test_MeetingPackController_unarchive(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();
        $plan = MeetingPack::where('status',MeetingPackStatus::Archived)->first();

        $response = $this->actingAs($user)
        ->post("/admin/meeting-packs/{$plan->id}/unarchive");

        $response->assertStatus(302);

        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'status' => MeetingPackStatus::Draft->value,
        ]);
    
    }

    }

