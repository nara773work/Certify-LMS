<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaBoard;

use App\Enums\UserRole;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QaReplyControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    /**
     * 受講生は回答を作成できる
     * バリデーションを通過したデータはDBに保存される
     */
    public function test_qa_reply_controller_store_succses(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Student)->first();
        $thread = QaThread::first();

        $data = [
            'qa_thread_id' => $thread->id,
            'body' => 'test',
            'user_id' => $user->id,
        ];

        $response = $this->actingAs($user)
            ->post("/qa-board/{$thread->id}/replies", $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('qa_replies', [
            'body' => 'test',
        ]);

    }

    /**
     * バリデーションを通過しなかったデータはDBに保存されない
     */
    public function test_qa_reply_controller_store_error(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Student)->first();
        $thread = QaThread::first();

        $data = [
            'qa_thread_id' => $thread->id,
            'body' => '',
            'user_id' => $user->id,
        ];

        $response = $this->actingAs($user)
            ->post("/qa-board/{$thread->id}/replies", $data);

        $response->assertSessionHasErrors([
            'body',
        ]);

    }

    /**
     * コーチは回答を作成することができる
     * バリデーションを通過したデータはDBに保存される
     */
    public function test_qa_reply_controller_store_coach(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Coach)->first();
        $thread = QaThread::first();

        $data = [
            'qa_thread_id' => $thread->id,
            'body' => 'test',
            'user_id' => $user->id,
        ];

        $response = $this->actingAs($user)
            ->post("/qa-board/{$thread->id}/replies", $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('qa_replies', [
            'body' => 'test',
        ]);

    }

    /**
     * 作成者は回答編集画面が表示される
     */
    public function test_qa_reply_controller_edit_student(): void
    {
        $this->seed();

        $thread = QaThread::has('replies')->first();
        $reply = $thread->replies()->first();
        $user = $reply->user;

        $response = $this->actingAs($user)
            ->get("/qa-board/{$thread->id}/replies/{$reply->id}/edit");

        $response->assertStatus(200);

        $response->assertviewIs('qa-thread.reply-edit');

        $response->assertSee($reply->body);

    }

    /**
     * 作成者以外は回答編集画面が表示されない
     */
    public function test_qa_reply_controller_edit_student_403(): void
    {
        $this->seed();

        $thread = QaThread::has('replies')->first();
        $reply = $thread->replies()->first();
        $user = User::where('id', '!=', $reply->user_id)->first();

        $response = $this->actingAs($user)
            ->get("/qa-board/{$thread->id}/replies/{$reply->id}/edit");

        $response->assertStatus(403);

    }

    /**
     * 作成者は質問が更新できる
     * バリデーションを通過したデータはDBに保存される
     */
    public function test_qa_reply_controller_update_student(): void
    {
        $this->seed();

        $thread = QaThread::has('replies')->first();
        $reply = $thread->replies()->first();
        $user = $reply->user;

        $update_data = [
            'body' => 'test_edited',
        ];

        $response = $this->actingAs($user)
            ->patch("/qa-board/{$thread->id}/replies/{$reply->id}", $update_data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('qa_replies', [
            'id' => $reply->id,
            'body' => 'test_edited',
        ]);

    }

    /**
     * 作成者は回答を削除できる
     * DBからも削除される
     */
    public function test_qa_reply_controller_delete_success_student(): void
    {
        $this->seed();

        $thread = QaThread::has('replies')->first();
        $reply = $thread->replies()->first();
        $user = $reply->user;

        $response = $this->actingAs($user)
            ->delete("/qa-board/{$thread->id}/replies/{$reply->id}");

        $response->assertStatus(302);

        $this->assertDatabaseMissing('qa_replies', [
            'id' => $reply->id,
        ]);
    }

    /**
     * 管理者は回答を削除できる
     */
    public function test_qa_reply_controller_delete_success_admin(): void
    {
        $this->seed();

        $thread = QaThread::has('replies')->first();
        $reply = $thread->replies()->first();
        $user = User::where('role', UserRole::Admin)->first();

        $response = $this->actingAs($user)
            ->delete("/admin/qa-board/{$thread->id}/replies/{$reply->id}");

        $response->assertStatus(302);

        $this->assertDatabaseMissing('qa_replies', [
            'id' => $reply->id,
        ]);

    }
}
