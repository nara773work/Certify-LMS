<?php

namespace Tests\Feature\Http\Announcement;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Enums\UserRole;
use App\Models\User;
use App\Models\Announcement;
use App\Enums\AnnouncementTargetType;

class AnnouncementRequestTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;
    public function test_title_required(): void
    {
        $this->seed();

        $user = User::where('role',UserRole::Admin)->first();

        $data = [
            'title' => '',
            'body' => 'test',
            'target_type' => AnnouncementTargetType::AllStudents->value,
        ];

        $response = $this->actingAs($user)->post('/admin/announcements',$data);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('title');
    }

    public function test_title_255(): void
    {
        $this->seed();

        $user = User::where('role',UserRole::Admin)->first();

        $data = [
            'title' => str_repeat('あ', 255),
            'body' => 'test',
            'target_type' => AnnouncementTargetType::AllStudents->value,
        ];

        $response = $this->actingAs($user)->post('/admin/announcements',$data);

        $response->assertStatus(302);
        $this->assertDatabaseHas('announcements',[
            'title' => str_repeat('あ', 255),
            'body' => 'test',
            'target_type' => AnnouncementTargetType::AllStudents->value,
        ]);
    }

    public function test_title_256(): void
    {
        $this->seed();

        $user = User::where('role',UserRole::Admin)->first();

        $data = [
            'title' => str_repeat('あ', 256),
            'body' => 'test',
            'target_type' => AnnouncementTargetType::AllStudents->value,
        ];

        $response = $this->actingAs($user)->post('/admin/announcements',$data);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('title');
    }

    public function test_body_required(): void
    {
        $this->seed();

        $user = User::where('role',UserRole::Admin)->first();

        $data = [
            'title' => 'test',
            'body' => '',
            'target_type' => AnnouncementTargetType::AllStudents->value,
        ];

        $response = $this->actingAs($user)->post('/admin/announcements',$data);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('body');
    }

    public function test_body_5000(): void
    {
        $this->seed();

        $user = User::where('role',UserRole::Admin)->first();

        $data = [
            'title' => 'test',
            'body' => str_repeat('あ', 5000),
            'target_type' => AnnouncementTargetType::AllStudents->value,
        ];

        $response = $this->actingAs($user)->post('/admin/announcements',$data);

        $response->assertStatus(302);
        $this->assertDatabaseHas('announcements',[
            'title' => 'test',
            'body' => str_repeat('あ', 5000),
            'target_type' => AnnouncementTargetType::AllStudents->value,
        ]);
    }

    public function test_body_5001(): void
    {
        $this->seed();

        $user = User::where('role',UserRole::Admin)->first();

        $data = [
            'title' => 'test',
            'body' => str_repeat('あ', 5001),
            'target_type' => AnnouncementTargetType::AllStudents->value,
        ];

        $response = $this->actingAs($user)->post('/admin/announcements',$data);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('body');
    }
}
