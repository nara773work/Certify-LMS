<?php

namespace Tests\Feature\Http\Part;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\Certification;

class AuthorizationTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;
    //教材管理画面
    public function test_admin_can_see_certification(): void
    {
        $this->seed();

        $admin = User::where('role', UserRole::Admin)->first();

        $certification = Certification::where(
            'name',
            '基本情報技術者試験'
        )->first();

        $response = $this->actingAs($admin)
            ->get("/admin/certifications/{$certification->id}");

        $response->assertOk();
    }

    public function test_coach_can_see_assigned_certification(): void
    {
        $this->seed();

        $coach = User::where(
            'email',
            'coach@certify-lms.test'
        )->first();

        $certification = Certification::where(
            'name',
            '基本情報技術者試験'
        )->first();

        $response = $this->actingAs($coach)
            ->get("/admin/certifications/{$certification->id}");

        $response->assertOk();
    }

    public function test_coach_cannot_see_unassigned_certification(): void
    {
        $this->seed();

        $coach = User::where(
            'email',
            'coach2@certify-lms.test'
        )->first();

        $certification = Certification::where(
            'name',
            '基本情報技術者試験'
        )->first();

        $response = $this->actingAs($coach)
            ->get("/admin/certifications/{$certification->id}");

        $response->assertForbidden();
    }

    //part
    public function test_admin_can_see_part(): void
    {
        $this->seed();

        $admin = User::where('role', UserRole::Admin)->first();

        $certification = Certification::where(
            'name',
            '基本情報技術者試験'
        )->first();

        $response = $this->actingAs($admin)
            ->get("/admin/certifications/{$certification->id}/parts");

        $response->assertOk();
    }

    public function test_coach_can_see_assigned_part(): void
    {
        $this->seed();

        $coach = User::where(
            'email',
            'coach@certify-lms.test'
        )->first();

        $certification = Certification::where(
            'name',
            '基本情報技術者試験'
        )->first();

        $part = $certification->parts()
            ->where('title', '第1部 基礎理論')
            ->first();

        $response = $this->actingAs($coach)
            ->get("/admin/parts/{$part->id}");

        $response->assertOk();
    }

    public function test_coach_cannot_see_unassigned_part(): void
    {
        $this->seed();

        $coach = User::where(
            'email',
            'coach2@certify-lms.test'
        )->first();

        $certification = Certification::where(
            'name',
            '基本情報技術者試験'
        )->first();

        $part = $certification->parts()
            ->where('title', '第1部 基礎理論')
            ->first();

        $response = $this->actingAs($coach)
            ->get("/admin/parts/{$part->id}");

        $response->assertForbidden();
    }

    //part store
    public function test_admin_can_create_part(): void
    {
        $this->seed();

        $admin = User::where('role', UserRole::Admin)->first();

        $certification = Certification::where(
            'name',
            '基本情報技術者試験'
        )->first();

        $data = [
            'name' => 'test',
            'description' => 'test',
        ];

        $response = $this->actingAs($admin)
            ->post(
                "/admin/certifications/{$certification->id}/parts",
                $data
            );

        $response->assertRedirect();
    }

    public function test_assigned_coach_can_create_part(): void
    {
        $this->seed();

        $coach = User::where(
            'email',
            'coach@certify-lms.test'
        )->first();

        $certification = Certification::where(
            'name',
            '基本情報技術者試験'
        )->first();

        $data = [
            'name' => 'test',
            'description' => 'test',
        ];

        $response = $this->actingAs($coach)
            ->post(
                "/admin/certifications/{$certification->id}/parts",
                $data
            );

        $response->assertRedirect();
    }


    public function test_unassigned_coach_cannot_create_part(): void
    {
        $this->seed();

        $coach = User::where(
            'email',
            'coach2@certify-lms.test'
        )->first();

        $certification = Certification::where(
            'name',
            '基本情報技術者試験'
        )->first();

        $data = [
            'name' => 'test',
            'description' => 'test',
        ];

        $response = $this->actingAs($coach)
            ->post(
                "/admin/certifications/{$certification->id}/parts",
                $data
            );

        $response->assertForbidden();
    }

    //part show
    public function test_Admin_can_see_part_show(): void
    {
        $this -> seed();
        $Admin = User::where('role', UserRole::Admin)->first();
        $certification = Certification::where(
            'name',
            '基本情報技術者試験'
        )->firstOrFail();

        $part = $certification->parts()
            ->where('title', '第1部 基礎理論')
            ->firstOrFail();

        $response = $this->actingAs($Admin)
        ->get("/admin/parts/{$part->id}");

        $response->assertStatus(200);
    }

    public function test_Coach_can_see_part_show(): void
    {
        $this -> seed();
        $Coach = User::where('email','coach@certify-lms.test')->first();
        $certification = Certification::where(
            'name',
            '基本情報技術者試験'
        )->firstOrFail();

        $part = $certification->parts()
            ->where('title', '第1部 基礎理論')
            ->firstOrFail();

        $response = $this->actingAs($Coach)
        ->get("/admin/parts/{$part->id}");

        $response->assertStatus(200);
    }

    public function test_Coach_cannot_see_part_show(): void
    {
        $this -> seed();
        $Coach = User::where('email','coach2@certify-lms.test')->first();
        $certification = Certification::where(
            'name',
            '基本情報技術者試験'
        )->firstOrFail();

        $part = $certification->parts()
            ->where('title', '第1部 基礎理論')
            ->firstOrFail();

        $response = $this->actingAs($Coach)
        ->get("/admin/parts/{$part->id}");

        $response->assertStatus(403);
    }

    //part update
    public function test_admin_can_update_part(): void
    {
        $this->seed();

        $admin = User::where('role', UserRole::Admin)->first();

        $certification = Certification::where(
            'name',
            '基本情報技術者試験'
        )->first();

        $part = $certification->parts()
            ->where('title', '第1部 基礎理論')
            ->first();

        $data = [
            'title' => 'test',
            'description' => 'test',
        ];

        $response = $this->actingAs($admin)
            ->patch("/admin/parts/{$part->id}", $data);

        $response->assertRedirect();
    }

    public function test_assigned_coach_can_update_part(): void
    {
        $this->seed();

        $coach = User::where(
            'email',
            'coach@certify-lms.test'
        )->first();

        $certification = Certification::where(
            'name',
            '基本情報技術者試験'
        )->first();

        $part = $certification->parts()
            ->where('title', '第1部 基礎理論')
            ->first();

        $data = [
            'title' => 'test',
            'description' => 'test',
        ];

        $response = $this->actingAs($coach)
            ->patch("/admin/parts/{$part->id}", $data);

        $response->assertRedirect();
    }

    public function test_unassigned_coach_cannot_update_part(): void
    {
        $this->seed();

        $coach = User::where(
            'email',
            'coach2@certify-lms.test'
        )->first();

        $certification = Certification::where(
            'name',
            '基本情報技術者試験'
        )->first();

        $part = $certification->parts()
            ->where('title', '第1部 基礎理論')
            ->first();

        $data = [
            'title' => 'test',
            'description' => 'test',
        ];

        $response = $this->actingAs($coach)
            ->patch("/admin/parts/{$part->id}", $data);

        $response->assertForbidden();
    }

    //part delete
    public function test_admin_can_delete_part(): void
    {
        $this->seed();

        $admin = User::where('role', UserRole::Admin)->first();

        $certification = Certification::where(
            'name',
            '基本情報技術者試験'
        )->first();

        $part = $certification->parts()
            ->where('title', '第1部 基礎理論')
            ->first();

        $response = $this->actingAs($admin)
            ->delete("/admin/parts/{$part->id}");

        $response->assertRedirect();
    }

    public function test_assigned_coach_can_delete_part(): void
    {
        $this->seed();

        $coach = User::where(
            'email',
            'coach@certify-lms.test'
        )->first();

        $certification = Certification::where(
            'name',
            '基本情報技術者試験'
        )->first();

        $part = $certification->parts()
            ->where('title', '第1部 基礎理論')
            ->first();

        $response = $this->actingAs($coach)
            ->delete("/admin/parts/{$part->id}");

        $response->assertRedirect();
    }

    public function test_unassigned_coach_cannot_delete_part(): void
    {
        $this->seed();

        $coach = User::where(
            'email',
            'coach2@certify-lms.test'
        )->first();

        $certification = Certification::where(
            'name',
            '基本情報技術者試験'
        )->first();

        $part = $certification->parts()
            ->where('title', '第1部 基礎理論')
            ->first();

        $response = $this->actingAs($coach)
            ->delete("/admin/parts/{$part->id}");

        $response->assertForbidden();
    }

}