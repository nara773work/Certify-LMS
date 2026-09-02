<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Settings;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    public function test_profile_edit_student(): void
    {
        $this->seed();

        $Student = User::where('role', UserRole::Student)->first();
        $response = $this->actingAs($Student)->get('/settings/profile');

        $response->assertStatus(200);

    }

    public function test_profile_edit_coach(): void
    {
        $this->seed();

        $Coach = User::where('role', UserRole::Coach)->first();
        $response = $this->actingAs($Coach)->get('/settings/profile');

        $response->assertStatus(200);
    }

    public function test_profile_edit_admin(): void
    {
        $this->seed();

        $Admin = User::where('role', UserRole::Admin)->first();
        $response = $this->actingAs($Admin)->get('/settings/profile');

        $response->assertStatus(200);
    }

    public function test_profile_store(): void
    {
        $this->seed();

        $Student = User::where('role', UserRole::Student)->first();

        $data = [
            'name' => 'test',
            'bio' => null,
        ];
        $response = $this->actingAs($Student)->patch('/settings/profile', $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('users',
            [
                'id' => $Student->id,
                'name' => 'test',
                'bio' => null,
            ]);

    }

    public function test_profile_store_email(): void
    {
        $this->seed();

        $Student = User::where('role', UserRole::Student)->first();

        $data = [
            'email' => 'test@example.com',
        ];
        $response = $this->actingAs($Student)->patch('/settings/profile', $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('users', [
            'id' => $Student->id,
            'email' => $Student->email,
        ]);

    }

    public function test_profile_avatar_store(): void
    {
        $this->seed();

        Storage::fake('public');

        $student = User::where('role', UserRole::Student->value)->firstOrFail();

        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($student)
            ->post('/settings/avatar', [
                'avatar' => $file,
            ]);

        $response->assertStatus(302);

        $this->assertNotNull(
            User::find($student->id)->avatar_url
        );
    }

    public function test_profile_avatar_delete(): void
    {
        $this->seed();

        $Student = User::where('email', 'student-graduated@certify-lms.test')
            ->firstOrFail();

        $response = $this->actingAs($Student)
            ->delete('/settings/avatar');

        $response->assertStatus(302);

        $this->assertDatabaseHas('users', [
            'id' => $Student->id,
            'avatar_url' => null,
        ]);

    }

    public function test_profile_password(): void
    {
        $this->seed();

        $Student = User::where('role', UserRole::Student->value)
            ->firstOrFail();

        $data = [
            'current_password' => 'password',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ];

        $response = $this->actingAs($Student)
            ->put('/settings/password', $data);

        $response->assertStatus(302);

        $this->assertTrue(
            Hash::check('12345678', $Student->password)
        );
    }
}
