<?php

namespace Tests\Feature\Http\QaBoard;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Certification;
use App\Models\QaThread;
use App\Enums\UserRole;

class QaBoardRequestTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    public function test_thread_certification_required(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Student)->first();
        $certification = Certification::first();

        $data = [
            'certification_id' => '',
            'title' => 'test',
            'body' => 'test',
            'user_id' => $user->id
        ];

        $response = $this->actingAs($user)
        ->post('/qa-board',$data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'certification_id',
        ]);
    }

    public function test_thread_certification_exists(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Student)->first();
        $certification = Certification::first();

        $data = [
            'certification_id' => 00000,
            'title' => 'test',
            'body' => 'test',
            'user_id' => $user->id
        ];

        $response = $this->actingAs($user)
        ->post('/qa-board',$data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'certification_id',
        ]);
    }

    public function test_thread_title_required(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Student)->first();
        $certification = Certification::first();

        $data = [
            'certification_id' => $certification->id,
            'title' => '',
            'body' => 'test',
            'user_id' => $user->id
        ];

        $response = $this->actingAs($user)
        ->post('/qa-board',$data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'title',
        ]);

    }

    public function test_thread_title_200(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Student)->first();
        $certification = Certification::first();

        $data = [
            'certification_id' => $certification->id,
            'title' => str_repeat('あ', 200),
            'body' => 'test',
            'user_id' => $user->id
        ];

        $response = $this->actingAs($user)
        ->post('/qa-board',$data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('qa_threads', [
            'title' => str_repeat('あ', 200),
            'body' => 'test',
        ]);
    }

    public function test_thread_title_201(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Student)->first();
        $certification = Certification::first();

        $data = [
            'certification_id' => $certification->id,
            'title' => str_repeat('あ', 201),
            'body' => 'test',
            'user_id' => $user->id
        ];

        $response = $this->actingAs($user)
        ->post('/qa-board',$data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'title',
        ]);
    }

    public function test_thread_body_required(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Student)->first();
        $certification = Certification::first();

        $data = [
            'certification_id' => $certification->id,
            'title' => 'test',
            'body' => '',
            'user_id' => $user->id
        ];

        $response = $this->actingAs($user)
        ->post('/qa-board',$data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'body',
        ]);

    }

    public function test_thread_body_5000(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Student)->first();
        $certification = Certification::first();

        $data = [
            'certification_id' => $certification->id,
            'title' => 'test',
            'body' => str_repeat('あ', 5000),
            'user_id' => $user->id
        ];

        $response = $this->actingAs($user)
        ->post('/qa-board',$data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('qa_threads', [
            'title' => 'test',
            'body' => str_repeat('あ', 5000),
        ]);
    }
    public function test_thread_body_5001(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Student)->first();
        $certification = Certification::first();

        $data = [
            'certification_id' => $certification->id,
            'title' => 'test',
            'body' => str_repeat('あ', 5001),
            'user_id' => $user->id
        ];

        $response = $this->actingAs($user)
        ->post('/qa-board',$data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'body',
        ]);
    }

    public function test_reply_body_required(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Coach)->first();
        $thread = QaThread::first();

        $data = [
            'qa_thread_id' => $thread->id,
            'body' => '',
        ];

        $response = $this->actingAs($user)
        ->post("/qa-board/{$thread->id}/replies",$data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'body',
        ]);
    }

    public function test_reply_body_5000(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Coach)->first();
        $thread = QaThread::first();

        $data = [
            'qa_thread_id' => $thread->id,
            'body' => str_repeat('あ', 5000),
        ];

        $response = $this->actingAs($user)
        ->post("/qa-board/{$thread->id}/replies",$data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('qa_replies', [
            'body' => str_repeat('あ', 5000),
        ]);
    }
    public function test_reply_body_5001(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Coach)->first();
        $thread = QaThread::first();

        $data = [
            'qa_thread_id' => $thread->id,
            'body' => str_repeat('あ', 5001),
        ];

        $response = $this->actingAs($user)
        ->post("/qa-board/{$thread->id}/replies",$data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'body',
        ]);
    }
    
}
