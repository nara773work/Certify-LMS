<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Enrollment;

use App\Models\Certification;
use App\Models\Enrollment;
use App\Models\EnrollmentNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentNoteControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    public function test_store_student(): void
    {
        $this->seed();
        $student = User::where('email', 'student@certify-lms.test')->firstOrFail();
        $toeic = Certification::where('name', 'TOEIC L&R 800 点コース')->firstOrFail();
        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('certification_id', $toeic->id)
            ->firstOrFail();

        $note = [
            'body' => 'test',
        ];

        $response = $this->actingAs($student)->post("/enrollments/{$enrollment->id}/notes", $note);

        $response->assertStatus(403);
    }

    public function test_store_i_tcoach_toeic(): void
    {
        $this->seed();
        $student = User::where('email', 'student@certify-lms.test')->firstOrFail();
        $ITCoach = User::where('email', 'coach@certify-lms.test')->firstOrFail();
        $toeic = Certification::where('name', 'TOEIC L&R 800 点コース')->firstOrFail();
        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('certification_id', $toeic->id)
            ->firstOrFail();
        $note = [
            'body' => 'test',
        ];

        $response = $this->actingAs($ITCoach)->post("/enrollments/{$enrollment->id}/notes", $note);

        $response->assertStatus(302);

        $this->assertDatabaseHas('enrollment_notes', [
            'enrollment_id' => $enrollment->id,
            'body' => 'test',
        ]);
    }

    public function test_store_i_tcoach_basic(): void
    {
        $this->seed();
        $student = User::where('email', 'student@certify-lms.test')->firstOrFail();
        $ITCoach = User::where('email', 'coach@certify-lms.test')->firstOrFail();
        $basic = Certification::where('name', '基本情報技術者試験')->firstOrFail();
        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('certification_id', $basic->id)
            ->firstOrFail();

        $note = [
            'body' => 'test',
        ];

        $response = $this->actingAs($ITCoach)->post("/enrollments/{$enrollment->id}/notes", $note);

        $response->assertStatus(302);

        $this->assertDatabaseHas('enrollment_notes', [
            'enrollment_id' => $enrollment->id,
            'body' => 'test',
        ]);
    }

    public function test_store_business_coach_boki(): void
    {
        $this->seed();
        $student = User::where('email', 'student@certify-lms.test')->firstOrFail();
        $businessCoach = User::where('email', 'coach2@certify-lms.test')->firstOrFail();
        $boki = Certification::where('name', '日商簿記 2 級')->firstOrFail();
        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('certification_id', $boki->id)
            ->firstOrFail();

        $note = [
            'body' => 'test',
        ];

        $response = $this->actingAs($businessCoach)->post("/enrollments/{$enrollment->id}/notes", $note);

        $response->assertStatus(302);

        $this->assertDatabaseHas('enrollment_notes', [
            'enrollment_id' => $enrollment->id,
            'body' => 'test',
        ]);
    }

    public function test_store_admin(): void
    {
        $this->seed();
        $student = User::where('email', 'student@certify-lms.test')->firstOrFail();
        $admin = User::where('email', 'admin@certify-lms.test')->firstOrFail();
        $boki = Certification::where('name', '日商簿記 2 級')->firstOrFail();
        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('certification_id', $boki->id)
            ->firstOrFail();
        $note = [
            'body' => 'test',
        ];

        $response = $this->actingAs($admin)->post("/enrollments/{$enrollment->id}/notes", $note);

        $response->assertStatus(302);

        $this->assertDatabaseHas('enrollment_notes', [
            'enrollment_id' => $enrollment->id,
            'body' => 'test',
        ]);
    }

    public function test_edit_i_tcoach(): void
    {
        $this->seed();

        $student = User::where('email', 'student@certify-lms.test')->firstOrFail();
        $ITCoach = User::where('email', 'coach@certify-lms.test')->firstOrFail();

        $basic = Certification::where('name', '基本情報技術者試験')->firstOrFail();
        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('certification_id', $basic->id)
            ->firstOrFail();
        $note = EnrollmentNote::where('enrollment_id', $enrollment->id)
            ->firstOrFail();

        $response = $this->actingAs($ITCoach)->get("/enrollment-notes/{$note->id}/edit");

        $response->assertStatus(200);
    }

    // 担当コーチが複数人いても他コーチのメモは操作できない
    public function test_edit_business_coach(): void
    {
        $this->seed();

        $student = User::where('email', 'student@certify-lms.test')->firstOrFail();
        $businessCoach = User::where('email', 'coach2@certify-lms.test')->firstOrFail();

        $toeic = Certification::where('name', 'TOEIC L&R 800 点コース')->firstOrFail();
        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('certification_id', $toeic->id)
            ->firstOrFail();
        $note = EnrollmentNote::where('enrollment_id', $enrollment->id)
            ->firstOrFail();

        $response = $this->actingAs($businessCoach)->get("/enrollment-notes/{$note->id}/edit");

        $response->assertStatus(403);
    }

    public function test_update_i_tcoach(): void
    {
        $this->seed();

        $student = User::where('email', 'student@certify-lms.test')->firstOrFail();
        $ITCoach = User::where('email', 'coach@certify-lms.test')->firstOrFail();

        $basic = Certification::where('name', '基本情報技術者試験')->firstOrFail();
        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('certification_id', $basic->id)
            ->firstOrFail();
        $note = EnrollmentNote::where('enrollment_id', $enrollment->id)
            ->firstOrFail();

        $data = [
            'body' => 'edit',
        ];

        $response = $this->actingAs($ITCoach)->patch("/enrollment-notes/{$note->id}", $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('enrollment_notes', [
            'enrollment_id' => $enrollment->id,
            'body' => 'edit',
        ]);
    }

    public function test_update_businesscoach(): void
    {
        $this->seed();

        $student = User::where('email', 'student@certify-lms.test')->firstOrFail();
        $businessCoach = User::where('email', 'coach2@certify-lms.test')->firstOrFail();

        $boki = Certification::where('name', '日商簿記 2 級')->firstOrFail();
        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('certification_id', $boki->id)
            ->firstOrFail();
        $note = EnrollmentNote::where('enrollment_id', $enrollment->id)
            ->firstOrFail();

        $data = [
            'body' => 'edit',
        ];

        $response = $this->actingAs($businessCoach)->patch("/enrollment-notes/{$note->id}", $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('enrollment_notes', [
            'enrollment_id' => $enrollment->id,
            'body' => 'edit',
        ]);
    }

    public function test_delete_i_tcoach(): void
    {
        $this->seed();

        $student = User::where('email', 'student@certify-lms.test')->firstOrFail();
        $ITCoach = User::where('email', 'coach@certify-lms.test')->firstOrFail();

        $basic = Certification::where('name', '基本情報技術者試験')->firstOrFail();
        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('certification_id', $basic->id)
            ->firstOrFail();
        $note = EnrollmentNote::where('enrollment_id', $enrollment->id)
            ->firstOrFail();

        $response = $this->actingAs($ITCoach)->delete("/enrollment-notes/{$note->id}");

        $response->assertStatus(302);

        $this->assertDatabaseMissing('enrollment_notes', [
            'enrollment_id' => $enrollment->id,
            'body' => 'edit',
        ]);
    }

    public function test_delete_businesscoach(): void
    {
        $this->seed();

        $student = User::where('email', 'student@certify-lms.test')->firstOrFail();
        $businessCoach = User::where('email', 'coach2@certify-lms.test')->firstOrFail();

        $boki = Certification::where('name', '日商簿記 2 級')->firstOrFail();
        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('certification_id', $boki->id)
            ->firstOrFail();
        $note = EnrollmentNote::where('enrollment_id', $enrollment->id)
            ->firstOrFail();

        $response = $this->actingAs($businessCoach)->delete("/enrollment-notes/{$note->id}");

        $response->assertStatus(302);

        $this->assertDatabaseMissing('enrollment_notes', [
            'enrollment_id' => $enrollment->id,
            'body' => 'edit',
        ]);
    }
}
