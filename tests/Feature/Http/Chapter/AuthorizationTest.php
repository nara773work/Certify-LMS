<?php

namespace Tests\Feature\Http\Chapter;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\Certification;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;
   //chapter
public function test_Admin_can_see_chapter(): void
{
    $this->seed();

    $admin = User::where('role', UserRole::Admin)->firstOrFail();

    $certification = Certification::where(
        'name',
        '基本情報技術者試験'
    )->firstOrFail();

    $part = $certification->parts()
        ->where('title', '第1部 基礎理論')
        ->firstOrFail();

    $chapter = $part->chapters()
        ->where('title', '第1章 進数と論理演算')
        ->firstOrFail();

    $response = $this->actingAs($admin)
        ->get("/admin/chapters/{$chapter->id}");

    $response->assertOk();
}

    public function test_Coach_can_see_chapter(): void
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

        $chapter = $part->chapters()
            ->where('title', '第1章 進数と論理演算')
            ->firstOrFail();

        $response = $this->actingAs($Coach)
            ->get("/admin/chapters/{$chapter->id}");

        $response->assertOk();
    }

    public function test_Coach_cannot_see_chapter(): void
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

        $chapter = $part->chapters()
            ->where('title', '第1章 進数と論理演算')
            ->firstOrFail();

        $response = $this->actingAs($Coach)
            ->get("/admin/chapters/{$chapter->id}");

        $response->assertStatus(403);
    }

    //chapter store
    public function test_Admin_can_see_chapter_store(): void
    {
        $this -> seed();
        $Admin = User::where('role',UserRole::Admin)->first();
        $certification = Certification::where(
            'name',
            '基本情報技術者試験'
        )->firstOrFail();

        $part = $certification->parts()
            ->where('title', '第1部 基礎理論')
            ->firstOrFail();

        $chapter = $part->chapters()
            ->where('title', '第1章 進数と論理演算')
            ->firstOrFail();

        $data = [
            'test',
            'description',
        ];

        $response = $this->actingAs($Admin)
            ->post("/admin/parts/{$part->id}/chapters",$data);

        $response->assertStatus(302);
    }

    public function test_Coach_can_see_chapter_store(): void
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

        $chapter = $part->chapters()
            ->where('title', '第1章 進数と論理演算')
            ->firstOrFail();

        $data = [
            'test',
            'description',
        ];

        $response = $this->actingAs($Coach)
            ->post("/admin/parts/{$part->id}/chapters",$data);

        $response->assertStatus(302);
    }

    public function test_Coach_cannot_see_chapter_store(): void
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

        $chapter = $part->chapters()
            ->where('title', '第1章 進数と論理演算')
            ->firstOrFail();

        $data = [
            'test',
            'description',
        ];

        $response = $this->actingAs($Coach)
            ->post("/admin/parts/{$part->id}/chapters",$data);

        $response->assertStatus(403);
    }

    //chapter show
    public function test_Admin_can_see_chapter_show(): void
    {
        $this -> seed();
        $Admin = User::where('role',UserRole::Admin)->first();
        $certification = Certification::where(
            'name',
            '基本情報技術者試験'
        )->firstOrFail();

        $part = $certification->parts()
            ->where('title', '第1部 基礎理論')
            ->firstOrFail();

        $chapter = $part->chapters()
            ->where('title', '第1章 進数と論理演算')
            ->firstOrFail();

        $data = [
            'test',
            'description',
        ];

        $response = $this->actingAs($Admin)
            ->get("/admin/chapters/{$chapter->id}");

        $response->assertStatus(200);
    }

    public function test_Coach_can_see_chapter_show(): void
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

        $chapter = $part->chapters()
            ->where('title', '第1章 進数と論理演算')
            ->firstOrFail();

        $data = [
            'test',
            'description',
        ];

        $response = $this->actingAs($Coach)
            ->get("/admin/chapters/{$chapter->id}");

        $response->assertOk();
    }

    public function test_Coach_cannot_see_chapter_show(): void
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

        $chapter = $part->chapters()
            ->where('title', '第1章 進数と論理演算')
            ->firstOrFail();

        $data = [
            'test',
            'description',
        ];

        $response = $this->actingAs($Coach)
            ->get("/admin/chapters/{$chapter->id}");

        $response->assertStatus(403);
    }

    //chapter update
    public function test_Admin_can_see_chapter_update(): void
    {
        $this -> seed();
        $Admin = User::where('role',UserRole::Admin)->first();
        $certification = Certification::where(
            'name',
            '基本情報技術者試験'
        )->firstOrFail();

        $part = $certification->parts()
            ->where('title', '第1部 基礎理論')
            ->firstOrFail();

        $chapter = $part->chapters()
            ->where('title', '第1章 進数と論理演算')
            ->firstOrFail();

        $data = [
            'test',
            'description',
        ];

        $response = $this->actingAs($Admin)
            ->patch("/admin/chapters/{$chapter->id}",$data);

        $response->assertStatus(302);
    }

    public function test_Coach_can_see_chapter_update(): void
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

        $chapter = $part->chapters()
            ->where('title', '第1章 進数と論理演算')
            ->firstOrFail();

        $data = [
            'test',
            'description',
        ];

        $response = $this->actingAs($Coach)
            ->patch("/admin/chapters/{$chapter->id}",$data);

        $response->assertStatus(302);
    }

    public function test_Coach_cannot_see_chapter_update(): void
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

        $chapter = $part->chapters()
            ->where('title', '第1章 進数と論理演算')
            ->firstOrFail();

        $data = [
            'test',
            'description',
        ];

        $response = $this->actingAs($Coach)
            ->patch("/admin/chapters/{$chapter->id}",$data);

        $response->assertStatus(403);
    }

    //chapter delete
    public function test_Admin_can_see_chapter_delete(): void
    {
        $this -> seed();
        $Admin = User::where('role',UserRole::Admin)->first();
        $certification = Certification::where(
            'name',
            '基本情報技術者試験'
        )->firstOrFail();

        $part = $certification->parts()
            ->where('title', '第1部 基礎理論')
            ->firstOrFail();

        $chapter = $part->chapters()
            ->where('title', '第1章 進数と論理演算')
            ->firstOrFail();

        $data = [
            'test',
            'description',
        ];

        $response = $this->actingAs($Admin)
            ->delete("/admin/chapters/{$chapter->id}",$data);

        $response->assertStatus(302);
    }

    public function test_Coach_can_see_chapter_delete(): void
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

        $chapter = $part->chapters()
            ->where('title', '第1章 進数と論理演算')
            ->firstOrFail();

        $data = [
            'test',
            'description',
        ];

        $response = $this->actingAs($Coach)
            ->delete("/admin/chapters/{$chapter->id}",$data);

        $response->assertStatus(302);
    }

    public function test_Coach_cannot_see_chapter_delete(): void
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

        $chapter = $part->chapters()
            ->where('title', '第1章 進数と論理演算')
            ->firstOrFail();

        $data = [
            'test',
            'description',
        ];

        $response = $this->actingAs($Coach)
            ->delete("/admin/chapters/{$chapter->id}",$data);

        $response->assertStatus(403);
    }

}
