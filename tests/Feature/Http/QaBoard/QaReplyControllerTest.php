<?php

namespace Tests\Feature\Http\QaBoard;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\QaThread;

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
        public function test_QaReplyController_store_succses(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Student)->first();
        $thread = QaThread::first();

        $data = [
            'qa_thread_id' => $thread->id,
            'body' => 'test',
            'user_id' => $user->id
        ];

        $response = $this->actingAs($user)
        ->post("/qa-board/{$thread->id}/replies",$data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('qa_replies', [
            'body' => 'test',
        ]);

    }

    /**
     * バリデーションを通過しなかったデータはDBに保存されない
     */
    public function test_QaReplyController_store_error(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Student)->first();
        $thread = QaThread::first();

        $data = [
            'qa_thread_id' => $thread->id,
            'body' => '',
            'user_id' => $user->id
        ];

        $response = $this->actingAs($user)
        ->post("/qa-board/{$thread->id}/replies",$data);

        $response->assertSessionHasErrors([
           'body',
        ]);

    }

    /**
     * コーチは回答を作成することができる
     * バリデーションを通過したデータはDBに保存される
     */
    public function test_QaReplyController_store_Coach(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Coach)->first();
        $thread = QaThread::first();

        $data = [
            'qa_thread_id' => $thread->id,
            'body' => 'test',
            'user_id' => $user->id
        ];

        $response = $this->actingAs($user)
        ->post("/qa-board/{$thread->id}/replies",$data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('qa_replies', [
            'body' => 'test',
        ]);

    }

    /**
     * 作成者は回答編集画面が表示される
     */
    public function test_QaReplyController_edit_Student(): void
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
    public function test_QaReplyController_edit_Student_403(): void
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
    public function test_QaReplyController_update_Student(): void
    {
        $this->seed();

        $thread = QaThread::has('replies')->first();
        $reply = $thread->replies()->first();
        $user = $reply->user;

        $update_data = [
            'body' => 'test_edited',
        ];

        $response = $this->actingAs($user)
        ->patch("/qa-board/{$thread->id}/replies/{$reply->id}",$update_data);

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
    public function test_QaReplyController_delete_success_Student(): void
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
    public function test_QaReplyController_delete_success_Admin(): void
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
