<?php

namespace Tests\Feature\Http\Enrollment;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Certification;
use App\Models\Enrollment;
use App\Models\EnrollmentNote;

class EnrollmentNoteRequestTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;
    public function test_body_required(): void
    {
        $this->seed();
        $student = User::where('email', 'student@certify-lms.test')->firstOrFail();
        $ITCoach = User::where('email', 'coach@certify-lms.test')->firstOrFail();
        $toeic = Certification::where('name', 'TOEIC L&R 800 点コース')->firstOrFail();
        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('certification_id', $toeic->id)
            ->firstOrFail();
        $note = [
            'body' => '',
        ];

        $response = $this->actingAs($ITCoach)->post("/enrollments/{$enrollment->id}/notes",$note);

        $response->assertStatus(302);

        $response->assertSessionHasErrors('body');
    }

    public function test_body_2000(): void
    {
        $this->seed();
        $student = User::where('email', 'student@certify-lms.test')->firstOrFail();
        $ITCoach = User::where('email', 'coach@certify-lms.test')->firstOrFail();
        $toeic = Certification::where('name', 'TOEIC L&R 800 点コース')->firstOrFail();
        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('certification_id', $toeic->id)
            ->firstOrFail();
        $note = [
            'body' => str_repeat('あ', 2000),
        ];

        $response = $this->actingAs($ITCoach)->post("/enrollments/{$enrollment->id}/notes",$note);

        $response->assertStatus(302);

        $this->assertDatabaseHas('enrollment_notes',[
            'enrollment_id' => $enrollment->id,
            'body' => str_repeat('あ', 2000),
        ]);
    }

    public function test_body_2001(): void
    {
        $this->seed();
        $student = User::where('email', 'student@certify-lms.test')->firstOrFail();
        $ITCoach = User::where('email', 'coach@certify-lms.test')->firstOrFail();
        $toeic = Certification::where('name', 'TOEIC L&R 800 点コース')->firstOrFail();
        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('certification_id', $toeic->id)
            ->firstOrFail();
        $note = [
            'body' => str_repeat('あ', 2001),
        ];

        $response = $this->actingAs($ITCoach)->post("/enrollments/{$enrollment->id}/notes",$note);

        $response->assertStatus(302);

        $response->assertSessionHasErrors('body');
    }
}
