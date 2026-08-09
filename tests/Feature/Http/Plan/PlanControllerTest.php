<?php

namespace Tests\Feature\Http\Plan;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Plan;
use App\Enums\UserRole;
use App\Enums\PlanStatus;

class PlanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_PlanController_index_student(): void
    {
        $this->seed();

        $user = User::where('role',UserRole::Student)->first();

        $response = $this->actingAs($user)->get('/admin/plans');
        $response->assertStatus(403);

    }

        public function test_PlanController_index_coach(): void
    {
        $this->seed();

        $user = User::where('role',UserRole::Coach)->first();

        $response = $this->actingAs($user)->get('/admin/plans');
        $response->assertStatus(403);

    }

    public function test_PlanController_index(): void
    {
        $this->seed();

        $user = User::where('role',UserRole::Admin)->first();

        $response = $this->actingAs($user)->get('/admin/plans');
        $response->assertStatus(200);

    }

    public function test_PlanController_index_keyword(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $keyword = '1';

        $response = $this->actingAs($user)
        ->get('/admin/plans?&keyword='.$keyword);

        $response->assertStatus(200);

        $response->assertSee('1 ヶ月プラン 4 回');
        $response->assertSee('3 ヶ月プラン 12 回');

        $response->assertDontSee('6 ヶ月プラン 24 回');

    }

        public function test_PlanController_index_filter_status(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $response = $this->actingAs($user)
        ->get('/admin/plans?status='.PlanStatus::Published->value);

        $response->assertStatus(200);

        $response->assertSee('公開中');

        $response->assertSee('3 ヶ月プラン 12 回');
        $response->assertDontSee('新プラン(検討中)');

    }

    public function test_PlanController_create(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $response = $this->actingAs($user)
        ->get('/admin/plans/create');

        $response->assertStatus(200);

    }

    /**
     * バリデーションを通過したデータは、DBに保存される
     */
    public function test_PlanController_store_succses(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => 'test',
            'description' => 'test',
            'duration_days' => 10,
            'default_meeting_quota'=>10,
            'sort_order' => 10,
        ];

        $response = $this->actingAs($user)
        ->post('/admin/plans',$data);
        

        $response->assertStatus(302);

        $this->assertDatabaseHas('plans', [
            'name' => 'test',
            'description' => 'test',
            'duration_days' => 10,
            'default_meeting_quota'=>10,
            'sort_order' => 10,
        ]);

    }

    /**
     * バリデーションを通過しなかったデータはDBに保存されない
     */
    public function test_PlanController_store_error(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $data = [
            'name' => '',
            'description' => '',
            'duration_days' => 10,
            'default_meeting_quota'=>10,
            'sort_order' => 10,
        ];

        $response = $this->actingAs($user)
        ->post('/admin/plans',$data);

        $response->assertSessionHasErrors([
            'name' ,
        ]);
    }

        public function test_PlanController_show(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();
        $plan = Plan::first();

        $response = $this->actingAs($user)
        ->get("/admin/plans/{$plan->id}");

        $response->assertStatus(200);

        $response->assertviewIs('plan.management.show');

        $response->assertSee($plan->name);
        
    }

    public function test_PlanController_edit(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();
        $plan = Plan::first();

        $response = $this->actingAs($user)
        ->get("/admin/plans/{$plan->id}/edit");

        $response->assertStatus(200);

        $response->assertviewIs('plan.management.edit');

        $response->assertSee($plan->name);
    
    }

    public function test_PlanController_update(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();
        $plan = Plan::first();

        $update_data = [
            'name' => 'update',
            'description' => '',
            'duration_days' => 10,
            'default_meeting_quota'=>10,
            'sort_order' => 10,
        ];

        $response = $this->actingAs($user)
        ->put("/admin/plans/{$plan->id}",$update_data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('plans', [
            'name' => 'update',
            'description' => null,
            'duration_days' => 10,
            'default_meeting_quota'=>10,
            'sort_order' => 10,
        ]);
    
    }

    /**
     * 投稿者は回答がないスレッドは削除できる
     * DBからも削除される
     */
    public function test_PlanController_delete_success(): void
    {
        $this->seed();

        //logを持たず、下書きのユーザーがシーダーでいなかったので生成する
        $user = User::where('role', UserRole::Admin)->first();
        $plan = Plan::factory()->draft()->create([
            'created_by_user_id' => $user->id,
            'updated_by_user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->delete("/admin/plans/{$plan->id}");

        $response->assertStatus(302);

        $this->assertDatabaseMissing('plans', [
            'id' => $plan->id,
        ]);
    }

    public function test_PlanController_delete_error(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();
        $plan = Plan::where('status',PlanStatus::Published)->first();

        $response = $this->actingAs($user)
        ->delete("/admin/plans/{$plan->id}");

        $response->assertStatus(302);

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
        ]);
    
    }

    public function test_PlanController_publish(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();
        $plan = Plan::where('status',PlanStatus::Draft)->first();

        $response = $this->actingAs($user)
        ->post("/admin/plans/{$plan->id}/publish");

        $response->assertStatus(302);

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'status' => PlanStatus::Published->value,
        ]);
    
    }

    public function test_PlanController_archive(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();
        $plan = Plan::where('status',PlanStatus::Published)->first();

        $response = $this->actingAs($user)
        ->post("/admin/plans/{$plan->id}/archive");

        $response->assertStatus(302);

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'status' => PlanStatus::Archived->value,
        ]);
    
    }

    public function test_PlanController_unarchive(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();
        $plan = Plan::where('status',PlanStatus::Archived)->first();

        $response = $this->actingAs($user)
        ->post("/admin/plans/{$plan->id}/unarchive");

        $response->assertStatus(302);

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'status' => PlanStatus::Draft->value,
        ]);
    
    }

}
