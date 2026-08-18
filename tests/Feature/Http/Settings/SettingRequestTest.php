<?php

namespace Tests\Feature\Http\Settings;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class SettingRequestTest extends TestCase
{
    /**
     * A basic feature test example.
     */

    use RefreshDatabase;

    public function test_title_required(): void
    {
        $this->seed();

        $Student = User::where('role', UserRole::Student)->first();

        $data = [
            'name' => '',
            'bio'=> null
        ];

        $response = $this->actingAs($Student)
        ->patch('/settings/profile', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'name',
        ]);
        
    }

    public function test_title_255(): void
    {
        $this->seed();

        $Student = User::where('role', UserRole::Student)->first();

        $data = [
            'name' => str_repeat('あ', 50),
            'bio'=> null
        ];

        $response = $this->actingAs($Student)
        ->patch('/settings/profile', $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('users',
        [
            'id' => $Student->id,
            'name' => str_repeat('あ', 50),
            'bio'=> null
        ]);
    }

    public function test_title_256(): void
    {
        $this->seed();

        $Student = User::where('role', UserRole::Student)->first();

        $data = [
            'name' => str_repeat('あ', 51),
            'bio'=> null
        ];

        $response = $this->actingAs($Student)
        ->patch('/settings/profile', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors(
        [
            'name'
        ]);
    }

    public function test_body_1000(): void
    {
        $this->seed();

        $Student = User::where('role', UserRole::Student)->first();

        $data = [
            'name' => 'test',
            'bio'=> str_repeat('あ', 1000)
        ];

        $response = $this->actingAs($Student)
        ->patch('/settings/profile', $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('users',
        [
            'id' => $Student->id,
            'name' => 'test',
            'bio'=> str_repeat('あ', 1000)
        ]);
    }

    public function test_body_1001(): void
    {
        $this->seed();

        $Student = User::where('role', UserRole::Student)->first();

        $data = [
            'name' => 'test',
            'bio'=> str_repeat('あ', 1001),
        ];

        $response = $this->actingAs($Student)
        ->patch('/settings/profile', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'bio'
        ]);
    }

    public function test_avatar_image(): void
{
    $this->seed();

    $Student = User::where('role', UserRole::Student)->first();

    $file = UploadedFile::fake()->image('avatar.jpg');

    $response = $this->actingAs($Student)
        ->post('/settings/avatar', [
            'avatar' => $file,
        ]);

    $response->assertStatus(302);

    $response->assertSessionHasNoErrors();
}

    public function test_avatar_mimes(): void
{
    $this->seed();

    $Student = User::where('role', UserRole::Student)->first();

    $file = UploadedFile::fake()->create(
        'avatar.pdf',
        100,
        'application/pdf'
    );

    $response = $this->actingAs($Student)
        ->post('/settings/avatar', [
            'avatar' => $file,
        ]);

    $response->assertStatus(302);

    $response->assertSessionHasErrors([
        'avatar',
    ]);
}

    public function test_avatar_2MB(): void
{
    $this->seed();

    $Student = User::where('role', UserRole::Student)->first();

    $file = UploadedFile::fake()->create(
        'avatar.jpg',
        2048,
        'image/jpeg'
    );

    $response = $this->actingAs($Student)
        ->post('/settings/avatar', [
            'avatar' => $file,
        ]);

    $response->assertStatus(302);

    $response->assertSessionHasNoErrors();
}

    public function test_avatar_3MB(): void
{
    $this->seed();

    $Student = User::where('role', UserRole::Student)->first();

    $file = UploadedFile::fake()->create(
        'avatar.jpg',
        3072,
        'image/jpeg'
    );

    $response = $this->actingAs($Student)
        ->post('/settings/avatar', [
            'avatar' => $file,
        ]);

    $response->assertStatus(302);

    $response->assertSessionHasErrors([
        'avatar',
    ]);
}

    public function test_carrent_password_required(): void
    {
        $this->seed();

        $Student = User::where('role', UserRole::Student)->first();

        $data = [
            'current_password' => '',
            'password' => '',
            'password_confirmation' => ''
        ];

        $response = $this->actingAs($Student)
        ->put('/settings/password', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors(
        [
            'current_password',
            'password' 
        ]);
    }

    public function test_current_password_current(): void
    {
        $this->seed();

        $Student = User::where('role', UserRole::Student)->first();

        $data = [
            'current_password' => '00000000',
            'password' => '12345678',
            'password_confirmation' => '12345678'
        ];

        $response = $this->actingAs($Student)
        ->put('/settings/password', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors(
        [
            'current_password' 
        ]);
    }

    public function test_password_required(): void
    {
        $this->seed();

        $Student = User::where('role', UserRole::Student)->first();

        $data = [
            'current_password' => 'password',
            'password' => '',
            'password_confirmation' => '12345678'
        ];

        $response = $this->actingAs($Student)
        ->put('/settings/password', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors(
        [
            'password' 
        ]);
    }

    public function test_password_8(): void
    {
        $this->seed();

        $Student = User::where('role', UserRole::Student)->first();

        $data = [
            'current_password' => 'password',
            'password' => '12345678',
            'password_confirmation' => '12345678'
        ];

        $response = $this->actingAs($Student)
        ->put('/settings/password', $data);

        $response->assertStatus(302);

    $this->assertTrue(
        Hash::check(
            '12345678',
            $Student->fresh()->password
        )
    );
}

    public function test_password_7(): void
    {
        $this->seed();

        $Student = User::where('role', UserRole::Student)->first();

        $data = [
            'current_password' => 'password',
            'password' => '1234567',
            'password_confirmation' => '1234567'
        ];

        $response = $this->actingAs($Student)
        ->put('/settings/password', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors(
        [
            'password' 
        ]);
    }

    public function test_password_confirm(): void
    {
        $this->seed();

        $Student = User::where('role', UserRole::Student)->first();

        $data = [
            'current_password' => 'password',
            'password' => '12345678',
            'password_confirmation' => '11111111'
        ];

        $response = $this->actingAs($Student)
        ->put('/settings/password', $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors(
        [
            'password'
        ]);
    }
}
