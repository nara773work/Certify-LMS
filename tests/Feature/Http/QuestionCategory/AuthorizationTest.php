<?php

namespace Tests\Feature\Http\QuestionCategory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\Certification;
use App\Models\QuestionCategory;

class AuthorizationTest extends TestCase
{
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
            ->get("/admin/certifications/{$certification->id}/question-categories");

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
            ->get("/admin/certifications/{$certification->id}/question-categories");

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
            ->get("/admin/certifications/{$certification->id}/question-categories");

        $response->assertStatus(403);
    }

    // create
public function test_admin_can_see_question_category_create(): void
{
    $this->seed();

    $admin = User::where('role', UserRole::Admin)->firstOrFail();

    $certification = Certification::where(
        'name',
        '基本情報技術者試験'
    )->firstOrFail();

    $response = $this->actingAs($admin)
        ->get(
            "/admin/certifications/{$certification->id}/question-categories"
        );

    $response->assertOk();
}

public function test_coach_can_see_question_category_create(): void
{
    $this->seed();

    $coach = User::where(
        'email',
        'coach@certify-lms.test'
    )->firstOrFail();

    $certification = Certification::where(
        'name',
        '基本情報技術者試験'
    )->firstOrFail();

    $response = $this->actingAs($coach)
        ->get(
            "/admin/certifications/{$certification->id}/question-categories"
        );

    $response->assertOk();
}

public function test_coach_cannot_see_unassigned_question_category_create(): void
{
    $this->seed();

    $coach = User::where(
        'email',
        'coach2@certify-lms.test'
    )->firstOrFail();

    $certification = Certification::where(
        'name',
        '基本情報技術者試験'
    )->firstOrFail();

    $response = $this->actingAs($coach)
        ->get(
            "/admin/certifications/{$certification->id}/question-categories"
        );

    $response->assertStatus(403);
}

// edit
public function test_admin_can_see_question_category_edit(): void
{
    $this->seed();

    $admin = User::where('role', UserRole::Admin)->firstOrFail();

    $coach = User::where(
        'email',
        'coach@certify-lms.test'
    )->firstOrFail();

    $certification = Certification::where(
        'name',
        '基本情報技術者試験'
    )->firstOrFail();

    $questionCategory = QuestionCategory::where(
        'certification_id',
        $certification->id
    )->firstOrFail();

    $response = $this->actingAs($admin)
        ->get(
            "/admin/certifications/{$certification->id}/question-categories"
        );

    $response->assertOk();
}

public function test_coach_can_see_assigned_question_category_edit(): void
{
    $this->seed();

    $coach = User::where(
        'email',
        'coach@certify-lms.test'
    )->firstOrFail();

    $certification = Certification::where(
        'name',
        '基本情報技術者試験'
    )->firstOrFail();

    $questionCategory = QuestionCategory::where(
        'certification_id',
        $certification->id
    )->firstOrFail();

    $response = $this->actingAs($coach)
        ->get(
            "/admin/certifications/{$certification->id}/question-categories"
        );

    $response->assertOk();
}

public function test_coach_cannot_edit_unassigned_question_category(): void
{
    $this->seed();

    $coach = User::where(
        'email',
        'coach2@certify-lms.test'
    )->firstOrFail();

    $certification = Certification::where(
        'name',
        '基本情報技術者試験'
    )->firstOrFail();

    $questionCategory = QuestionCategory::where(
        'certification_id',
        $certification->id
    )->firstOrFail();

    $response = $this->actingAs($coach)
        ->get(
            "/admin/certifications/{$certification->id}/question-categories"
        );

    $response->assertStatus(403);
}

// update
public function test_admin_can_update_question_category(): void
{
    $this->seed();

    $admin = User::where('role', UserRole::Admin)->firstOrFail();

    $questionCategory = QuestionCategory::firstOrFail();

    $data = [
        'name' => $questionCategory->name,
        'surge' => 'test'
    ];

    $response = $this->actingAs($admin)
        ->patch(
            "/admin/question-categories/{$questionCategory->id}",
            $data
        );

    $response->assertStatus(302);
}

public function test_coach_can_update_assigned_question_category(): void
{
    $this->seed();

    $coach = User::where(
        'email',
        'coach@certify-lms.test'
    )->firstOrFail();

    $certification = Certification::where(
        'name',
        '基本情報技術者試験'
    )->firstOrFail();

    $questionCategory = QuestionCategory::where(
        'certification_id',
        $certification->id
    )->firstOrFail();

    $data = [
        'name' => $questionCategory->name,
        'surge' => 'test'
    ];

    $response = $this->actingAs($coach)
        ->patch(
            "/admin/question-categories/{$questionCategory->id}",
            $data
        );

    $response->assertStatus(302);
}

public function test_coach_cannot_update_unassigned_question_category(): void
{
    $this->seed();

    $coach = User::where(
        'email',
        'coach2@certify-lms.test'
    )->firstOrFail();

    $certification = Certification::where(
        'name',
        '基本情報技術者試験'
    )->firstOrFail();

    $questionCategory = QuestionCategory::where(
        'certification_id',
        $certification->id
    )->firstOrFail();

    $data = [
        'name' => $questionCategory->name,
        'surge' => 'test'
    ];

    $response = $this->actingAs($coach)
        ->patch(
            "/admin/question-categories/{$questionCategory->id}",
            $data
        );

    $response->assertStatus(403);
}

// delete
public function test_admin_can_delete_question_category(): void
{
    $this->seed();

    $admin = User::where('role', UserRole::Admin)->firstOrFail();

    $questionCategory = QuestionCategory::firstOrFail();

    $response = $this->actingAs($admin)
        ->delete(
            "/admin/question-categories/{$questionCategory->id}"
        );

    $response->assertStatus(302);
}

public function test_coach_can_delete_assigned_question_category(): void
{
    $this->seed();

    $coach = User::where(
        'email',
        'coach@certify-lms.test'
    )->firstOrFail();

    $certification = Certification::where(
        'name',
        '基本情報技術者試験'
    )->firstOrFail();

    $questionCategory = QuestionCategory::where(
        'certification_id',
        $certification->id
    )->firstOrFail();

    $response = $this->actingAs($coach)
        ->delete(
            "/admin/question-categories/{$questionCategory->id}"
        );

    $response->assertStatus(302);
}

public function test_coach_cannot_delete_unassigned_question_category(): void
{
    $this->seed();

    $coach = User::where(
        'email',
        'coach2@certify-lms.test'
    )->firstOrFail();

    $certification = Certification::where(
        'name',
        '基本情報技術者試験'
    )->firstOrFail();

    $questionCategory = QuestionCategory::where(
        'certification_id',
        $certification->id
    )->firstOrFail();

    $response = $this->actingAs($coach)
        ->delete(
            "/admin/question-categories/{$questionCategory->id}"
        );

    $response->assertStatus(403);
}

}
