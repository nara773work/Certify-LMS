<?php

namespace Tests\Feature\Http\Question;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\Certification;
use App\Models\Question;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function getSection()
    {
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

        return $chapter->sections()
            ->where('title', '1.1 2 進数の表現')
            ->firstOrFail();
    }

    public function test_Admin_can_see_question_index(): void
    {
        $this->seed();

        $admin = User::where('role', UserRole::Admin)
            ->firstOrFail();

        $section = $this->getSection();

        $response = $this->actingAs($admin)
            ->get("/admin/sections/{$section->id}/questions");

        $response->assertOk();
    }

    public function test_Coach_can_see_question_index(): void
    {
        $this->seed();

        $coach = User::where(
            'email',
            'coach@certify-lms.test'
        )->firstOrFail();

        $section = $this->getSection();

        $response = $this->actingAs($coach)
            ->get("/admin/sections/{$section->id}/questions");

        $response->assertOk();
    }

    public function test_Coach_cannot_see_question_index(): void
    {
        $this->seed();

        $coach = User::where(
            'email',
            'coach2@certify-lms.test'
        )->firstOrFail();

        $section = $this->getSection();

        $response = $this->actingAs($coach)
            ->get("/admin/sections/{$section->id}/questions");

        $response->assertStatus(403);
    }

    public function test_Admin_can_store_question(): void
    {
        $this->seed();

        $admin = User::where('role', UserRole::Admin)
            ->firstOrFail();

        $section = $this->getSection();

        $data = [
            'body' => 'test',
            'description' => null,
            'section_id' => $section->id,
        ];

        $response = $this->actingAs($admin)
            ->post(
                "/admin/sections/{$section->id}/questions",
                $data
            );

        $response->assertStatus(302);
    }

    public function test_Coach_can_store_question(): void
    {
        $this->seed();

        $coach = User::where(
            'email',
            'coach@certify-lms.test'
        )->firstOrFail();

        $section = $this->getSection();

        $data = [
            'body' => 'test',
            'description' => null,
            'section_id' => $section->id,
        ];

        $response = $this->actingAs($coach)
            ->post(
                "/admin/sections/{$section->id}/questions",
                $data
            );

        $response->assertStatus(302);
    }

    public function test_Coach_cannot_store_question(): void
    {
        $this->seed();

        $coach = User::where(
            'email',
            'coach2@certify-lms.test'
        )->firstOrFail();

        $section = $this->getSection();

        $data = [
            'body' => 'test',
            'description' => null,
            'section_id' => $section->id,
        ];

        $response = $this->actingAs($coach)
            ->post(
                "/admin/sections/{$section->id}/questions",
                $data
            );

        $response->assertStatus(403);
    }


    public function test_Admin_can_see_question(): void
    {
        $this->seed();

        $admin = User::where('role', UserRole::Admin)
            ->firstOrFail();

        $section = $this->getSection();

        $question = $section->questions()
            ->firstOrFail();

        $response = $this->actingAs($admin)
            ->get(
                "/admin/section-questions/{$question->id}"
            );

        $response->assertOk();
    }

    public function test_Coach_can_see_question(): void
    {
        $this->seed();

        $coach = User::where(
            'email',
            'coach@certify-lms.test'
        )->firstOrFail();

        $section = $this->getSection();

        $question = $section->questions()
            ->firstOrFail();

        $response = $this->actingAs($coach)
            ->get(
                "/admin/section-questions/{$question->id}"
            );

        $response->assertOk();
    }

    public function test_Coach_cannot_see_question(): void
    {
        $this->seed();

        $coach = User::where(
            'email',
            'coach2@certify-lms.test'
        )->firstOrFail();

        $section = $this->getSection();

        $question = $section->questions()
            ->firstOrFail();

        $response = $this->actingAs($coach)
            ->get(
                "/admin/section-questions/{$question->id}"
            );

        $response->assertStatus(403);
    }

    public function test_Admin_can_update_question(): void
    {
        $this->seed();

        $admin = User::where('role', UserRole::Admin)
            ->firstOrFail();

        $section = $this->getSection();

        $question = $section->questions()
            ->firstOrFail();

        $data = [
            'body' => 'test',
            'description' => null,
            'section_id' => $section->id,
        ];

        $response = $this->actingAs($admin)
            ->patch(
                "/admin/section-questions/{$question->id}",
                $data
            );

        $response->assertStatus(302);
    }

    public function test_Coach_can_update_question(): void
    {
        $this->seed();

        $coach = User::where(
            'email',
            'coach@certify-lms.test'
        )->firstOrFail();

        $section = $this->getSection();

        $question = $section->questions()
            ->firstOrFail();

        $data = [
            'body' => 'test',
            'description' => null,
            'section_id' => $section->id,
        ];

        $response = $this->actingAs($coach)
            ->patch(
                "/admin/section-questions/{$question->id}",
                $data
            );

        $response->assertStatus(302);
    }

    public function test_Coach_cannot_update_question(): void
    {
        $this->seed();

        $coach = User::where(
            'email',
            'coach2@certify-lms.test'
        )->firstOrFail();

        $section = $this->getSection();

        $question = $section->questions()
            ->firstOrFail();

        $data = [
            'body' => 'test',
            'description' => null,
            'section_id' => $section->id,
        ];

        $response = $this->actingAs($coach)
            ->patch(
                "/admin/section-questions/{$question->id}",
                $data
            );

        $response->assertStatus(403);
    }

    public function test_Admin_can_delete_question(): void
{
    $this->seed();

    $admin = User::where(
        'role',
        UserRole::Admin
    )->firstOrFail();

    $section = $this->getSection();

    $question = $section->questions()
        ->firstOrFail();

    // Answerが紐づいていないテスト用Questionを作成
    $question = $question->replicate();
    $question->save();

    $response = $this->actingAs($admin)
        ->delete(
            "/admin/section-questions/{$question->id}"
        );

    $response->assertStatus(302);
}

    public function test_Coach_can_delete_question(): void
{
    $this->seed();

    $coach = User::where(
        'email',
        'coach@certify-lms.test'
    )->firstOrFail();

    $section = $this->getSection();

    $question = $section->questions()
        ->firstOrFail();

    // Answerが紐づいていないテスト用Questionを作成
    $question = $question->replicate();
    $question->save();

    $response = $this->actingAs($coach)
        ->delete(
            "/admin/section-questions/{$question->id}"
        );

    $response->assertStatus(302);
}

    public function test_Coach_cannot_delete_question(): void
    {
        $this->seed();

        $coach = User::where(
            'email',
            'coach2@certify-lms.test'
        )->firstOrFail();

        $section = $this->getSection();

        $question = $section->questions()
            ->firstOrFail();

        $response = $this->actingAs($coach)
            ->delete(
                "/admin/section-questions/{$question->id}"
            );

        $response->assertStatus(403);
    }
}