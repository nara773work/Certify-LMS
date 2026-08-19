<?php

namespace Tests\Feature\Http\Chapter;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;
   //chapter
    public function test_Admin_can_see_chapter(): void
    {
        $this -> seed();
        $Admin = User::where('role',UserRole::Admin)->first();
        $chapter = $Admin->chapters()->where('name','第1章 進数と論理演算')->first();
        
        $response = $this->actingAs($Admin)
        ->get("/admin/chapters/{$chapter->id}");

        $response->assertStatus(200);
    }

    public function test_Coach_can_see_chapter(): void
    {
        $this -> seed();
        $Coach = User::where('email','coach@certify-lms.test')->first();
        $chapter = $Admin->chapters()->where('name','第1章 進数と論理演算')->first();
        
        $response = $this->actingAs($Coach)
        ->get("/admin/chapters/{$chapters->id}");

        $response->assertStatus(200);
    }

    public function test_Coach_cannot_see_chapter(): void
    {
        $this -> seed();
        $Coach = User::where('email','coach2@certify-lms.test')->first();
        $chapter = $Admin->chapters()->where('name','第1章 進数と論理演算')->first();
        
        $response = $this->actingAs($Coach)
        ->get("/admin/chapters/{$chapters->id}");

        $response->assertStatus(403);
    }

    //chapter store
    public function test_Admin_can_see_chapter_store(): void
    {
        $this -> seed();
        $Admin = User::where('role',UserRole::Admin)->first();
        $chapter = $Admin->chapters()->where('name','第1章 進数と論理演算')->first();
        
        $data = [
            'title' => 'test',
            'description' => 'test'
        ];

        $response = $this->actingAs($Admin)
        ->post("/admin/chapters/{$chapters->id}",$data);

        $response->assertStatus(302);
    }

    public function test_Coach_can_see_chapter_store(): void
    {
        $this -> seed();
        $Coach = User::where('email','coach@certify-lms.test')->first();
        $chapter = $Admin->chapters()->where('name','第1章 進数と論理演算')->first();
        
        $data = [
            'title' => 'test',
            'description' => 'test'
        ];

        $response = $this->actingAs($Coach)
        ->post("/admin/chapters/{$chapters->id}",$data);

        $response->assertStatus(302);
    }

    public function test_Coach_cannot_see_chapter_store(): void
    {
        $this -> seed();
        $Coach = User::where('email','coach2@certify-lms.test')->first();
        $chapter = $Admin->chapters()->where('name','第1章 進数と論理演算')->first();
        $data = [
            'title' => 'test',
            'description' => 'test'
        ];

        $response = $this->actingAs($Coach)
        ->get("/admin/chapters/{$chapters->id}",$data);

        $response->assertStatus(403);
    }

    //chapter show
    public function test_Admin_can_see_chapter_show(): void
    {
        $this -> seed();
        $Admin = User::where('role',UserRole::Admin)->first();
        $chapter = $Admin->chapters()->where('name','第1章 進数と論理演算')->first();

        $response = $this->actingAs($Admin)
        ->get("/admin/chapters/{$chapters->id}");

        $response->assertStatus(200);
    }

    public function test_Coach_can_see_chapter_show(): void
    {
        $this -> seed();
        $Coach = User::where('email','coach@certify-lms.test')->first();
        $chapter = $Admin->chapters()->where('name','第1章 進数と論理演算')->first();

        $response = $this->actingAs($Coach)
        ->get("/admin/chapters/{$chapters->id}");

        $response->assertStatus(200);
    }

    public function test_Coach_cannot_see_chapter_show(): void
    {
        $this -> seed();
        $Coach = User::where('email','coach2@certify-lms.test')->first();
        $chapter = $Admin->chapters()->where('name','第1章 進数と論理演算')->first();

        $response = $this->actingAs($Coach)
        ->get("/admin/chapters/{$chapters->id}");

        $response->assertStatus(403);
    }

    //chapter update
    public function test_Admin_can_see_chapter_update(): void
    {
        $this -> seed();
        $Admin = User::where('role',UserRole::Admin)->first();
        $chapter = $Admin->chapters()->where('name','第1章 進数と論理演算')->first();

        $data = [
            'title' => 'test',
            'description' => 'test'
        ];

        $response = $this->actingAs($Admin)
        ->update("/admin/chapters/{$chapters->id}",$data);

        $response->assertStatus(302);
    }

    public function test_Coach_can_see_chapter_update(): void
    {
        $this -> seed();
        $Coach = User::where('email','coach@certify-lms.test')->first();
        $chapter = $Admin->chapters()->where('name','第1章 進数と論理演算')->first();

        $data = [
            'title' => 'test',
            'description' => 'test'
        ];

        $response = $this->actingAs($Coach)
        ->update("/admin/chapters/{$chapters->id}",$data);

        $response->assertStatus(302);
    }

    public function test_Coach_cannot_see_chapter_update(): void
    {
        $this -> seed();
        $Coach = User::where('email','coach2@certify-lms.test')->first();
        $chapter = $Admin->chapters()->where('name','第1章 進数と論理演算')->first();

        $data = [
            'title' => 'test',
            'description' => 'test'
        ];

        $response = $this->actingAs($Coach)
        ->update("/admin/chapters/{$chapters->id}",$data);

        $response->assertStatus(403);
    }

    //chapter delete
    public function test_Admin_can_see_chapter_delete(): void
    {
        $this -> seed();
        $Admin = User::where('role',UserRole::Admin)->first();
        $chapter = $Admin->chapters()->where('name','第1章 進数と論理演算')->first();

        $response = $this->actingAs($Admin)
        ->delete("/admin/chapters/{$chapters->id}");

        $response->assertStatus(302);
    }

    public function test_Coach_can_see_chapter_delete(): void
    {
        $this -> seed();
        $Coach = User::where('email','coach@certify-lms.test')->first();
        $chapter = $Admin->chapters()->where('name','第1章 進数と論理演算')->first();

        $response = $this->actingAs($Coach)
        ->delete("/admin/chapters/{$chapters->id}");

        $response->assertStatus(302);
    }

    public function test_Coach_cannot_see_chapter_delete(): void
    {
        $this -> seed();
        $Coach = User::where('email','coach2@certify-lms.test')->first();
        $chapter = $Admin->chapters()->where('name','第1章 進数と論理演算')->first();

        $response = $this->actingAs($Coach)
        ->delete("/admin/chapters/{$chapters->id}");

        $response->assertStatus(403);
    }

}
