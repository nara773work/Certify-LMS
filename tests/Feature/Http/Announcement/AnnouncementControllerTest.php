<?php

namespace Tests\Feature\Http\Announcement;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Announcement;
use App\Enums\UserRole;
use App\Enums\AnnouncementTargetType;
use App\Models\Certification;
use App\Notifications\AnnouncementNotification;
use Illuminate\Support\Facades\Queue;

class AnnouncementControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    public function test_index_admin(): void
    {
        $this->seed();

        $user = User::where('role',UserRole::Admin)->first();

        $response = $this->actingAs($user)->get('/admin/announcements');

        $response->assertStatus(200);
    }

    public function test_index_coach(): void
    {
        $this->seed();

        $user = User::where('role',UserRole::Coach)->first();

        $response = $this->actingAs($user)->get('/admin/announcements');

        $response->assertStatus(403);
    }

        public function test_index_student(): void
    {
        $this->seed();

        $user = User::where('role',UserRole::Student)->first();

        $response = $this->actingAs($user)->get('/admin/announcements');

        $response->assertStatus(403);
    }

    public function test_create(): void
    {
        $this->seed();

        $user = User::where('role',UserRole::Admin)->first();

        $response = $this->actingAs($user)->get('/admin/announcements/create');

        $response->assertStatus(200);
    }

    public function test_store(): void
    {
        $this->seed();

        $user = User::where('role',UserRole::Admin)->first();

        $data = [
            'title' => 'test',
            'body' => 'test',
            'target_type' => AnnouncementTargetType::AllStudents->value,
        ];

        $response = $this->actingAs($user)->post('/admin/announcements',$data);

        $response->assertStatus(302);
        $this->assertDatabaseHas('announcements',[
            'title' => 'test',
            'body' => 'test',
            'target_type' => AnnouncementTargetType::AllStudents->value,
        ]);
    }

    public function test_store_certification(): void
{
    $this->seed();

    $admin = User::where('role', UserRole::Admin)->first();
    $certification = Certification::first();

    $student = User::whereHas('enrollments', function ($query) use ($certification) {
        $query->where('certification_id', $certification->id);
    })->first();

    $response = $this->actingAs($admin)->post('/admin/announcements', [
        'title' => '資格指定テスト',
        'body' => 'test',
        'target_type' => AnnouncementTargetType::Certification->value,
        'target_certification_id' => $certification->id,
    ]);

    $response->assertStatus(302);

    $announcement = Announcement::where('title', '資格指定テスト')->first();

    $this->assertTrue(
        $announcement->users->contains($student)
    );
}

    public function test_store_user(): void
{
    $this->seed();

    $admin = User::where('role', UserRole::Admin)->first();
    $student = User::where('role', UserRole::Student)->first();

    $response = $this->actingAs($admin)->post('/admin/announcements', [
        'title' => 'ユーザー指定テスト',
        'body' => 'test',
        'target_type' => AnnouncementTargetType::User->value,
        'target_user_id' => $student->id,
    ]);

    $response->assertStatus(302);

    $announcement = Announcement::where('title', 'ユーザー指定テスト')->first();

    $this->assertTrue(
        $announcement->users->contains($student)
    );
}

    public function test_show(): void
{
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();

        $announcement = Announcement::first();

        $response = $this->actingAs($user)
            ->get("/admin/announcements/{$announcement->id}");

        $response->assertStatus(200);
        $response->assertSee($announcement->title);
}

    public function test_notificationshow(): void
{
    $this->seed();

    $user = User::where('role', UserRole::Student)->first();

    $announcement = Announcement::first();

    $announcement->users()->sync([$user->id]);

    $user->notify(
        new AnnouncementNotification($announcement)
    );

    $notification = $user->notifications()
        ->where('type', AnnouncementNotification::class)
        ->latest()
        ->first();

    $response = $this->actingAs($user)
        ->get("/notifications/{$notification->id}");

    $response->assertStatus(200);
}

    public function test_notificationshow_other_user(): void
{
    $this->seed();

    $student1 = User::where('role', UserRole::Student)->first();

    $student2 = User::where('role', UserRole::Student)
        ->where('id', '!=', $student1->id)
        ->first();

    $notification = $student1->notifications()->first();

    $response = $this->actingAs($student2)
        ->get("/notifications/{$notification->id}");

    $response->assertStatus(404);
}

public function test_storeで通知がキューに投入される(): void
{
    Queue::fake();

    $this->seed();

    $admin = User::where('role', UserRole::Admin)->first();

    $studentCount = User::where('role', UserRole::Student)->count();

    $before = Queue::pushed(
    \Illuminate\Notifications\SendQueuedNotifications::class
)->count();

$response = $this->actingAs($admin)
    ->post('/admin/announcements', [
        'title' => 'キューテスト',
        'body' => 'キューに投入されるか確認',
        'target_type' => AnnouncementTargetType::AllStudents->value,
    ]);

$response->assertStatus(302);

$after = Queue::pushed(
    \Illuminate\Notifications\SendQueuedNotifications::class
)->count();

$this->assertSame(
    $studentCount * 2,
    $after - $before
);
}
}
