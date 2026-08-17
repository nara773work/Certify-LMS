<?php

namespace Tests\Feature\Http\QaBoard;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Certification;
use App\Models\QaThread;
use App\Enums\QaThreadStatus;

class QaThreadControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     * sail artisan test tests/Feature/QaThreadControllerTest.php
     */

    use RefreshDatabase;

    /**
     * ログイン済みユーザーで、かつ受講中ユーザーのみ閲覧することができる。
     * 受講済みユーザー以外は403の認証エラーを返す
     * 
     * 公開済み資格のみ閲覧できる
     */

    public function test_QaThreadController_index_Student(): void
    {
        $this->seed();

        $user = User::where('role',UserRole::Student)->first();

        $response = $this->actingAs($user)->get('/qa-board');
        $response->assertStatus(200);
        $response->assertViewIs('qa-thread.index');

        $user = User::where('role',UserRole::Student)
        ->where('status',UserStatus::Graduated)->first();

        $response = $this->actingAs($user)->get('/qa-board');
        $response->assertStatus(403);    
        
        $response->assertDontSee('AWS Certified Solutions Architect (下書き)');
        $response->assertDontSee('販売終了: Webクリエイター能力認定試験（アーカイブ）');

    }

    /**
     * ログイン済みコーチは閲覧することができる
     * 自分の担当教科のみ閲覧することができる
     * 
     * 公開済み資格のみ閲覧できる
     */
    public function test_QaThreadController_index_Coach_IT(): void
    {
        $this->seed();

        $user = User::where('role',UserRole::Coach)->first();

        $response = $this->actingAs($user)->get('/qa-board');
        $response->assertStatus(200);
        $response->assertViewIs('qa-thread.index');

        $response->assertSee('IPアドレスとサブネットマスクの計算方法が分かりません');
        $response->assertSee('情報セキュリティの午後問題が苦手です');
        $response->assertSee('TOEICのリスニングが聞き取れません'); 

        $response->assertDontSee('貸借対照表と損益計算書の違いが分かりません');
        $response->assertDontSee('クリティカルパスの求め方について教えてください');

        $response->assertDontSee('AWS Certified Solutions Architect (下書き)');
        $response->assertDontSee('販売終了: Webクリエイター能力認定試験（アーカイブ）');

    }

    /**
     * 全ての資格を閲覧することができる
     */
    public function test_QaThreadController_index_Admin(): void
    {
        $this->seed();

        $user = User::where('role',UserRole::Admin)->first();

        $response = $this->actingAs($user)->get('/admin/qa-board');
        $response->assertStatus(200);
        $response->assertViewIs('qa-thread.index');

        $response->assertSee('AWS Certified Solutions Architect (準備中)');

    }

    /**
     * 資格別で条件を絞ることができる
     */
    public function test_QaThreadController_index_filter_certification(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Student)->first();

        $certification = Certification::where('name', '基本情報技術者試験')->first();

        $response = $this->actingAs($user)
        ->get('/qa-board?certification_id='.$certification->id);

        $response->assertStatus(200);

        $response->assertSee('IPアドレスとサブネットマスクの計算方法が分かりません');

        $response->assertDontSee('TOEICのリスニングが聞き取れません');

    }

    /**
     * ステータスで条件を絞ることができる
     */
    public function test_QaThreadController_index_filter_status(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Student)->first();

        $response = $this->actingAs($user)
        ->get('/qa-board?status='.QaThreadStatus::Open->value);

        $response->assertStatus(200);

        $response->assertSee('対応中');
        $response->assertSee('未回答');

        $response->assertDontSee('✓解決済');

    }

    /**
     * キーワード検索をすることができる
     */
    public function test_QaThreadController_index_filter_keyword(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Student)->first();

        $keyword = '貸借対照表';

        $response = $this->actingAs($user)
        ->get('/qa-board?&keyword='.$keyword);

        $response->assertStatus(200);

        $response->assertSee('貸借対照表と損益計算書の違いが分かりません');

        $response->assertDontSee('TOEICのリスニングが聞き取れません');

    }

    /**
     * 質問スレッドの新規登録画面が表示される
     * コーチには表示されない
     */
    public function test_QaThreadController_create(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Student)->first();

        $response = $this->actingAs($user)
        ->get('/qa-board/create');

        $response->assertStatus(200);

        $response->assertSee('基本情報技術者試験');

        $user = User::where('role', UserRole::Coach)->first();

        $response = $this->actingAs($user)
        ->get('/qa-board/create');

        $response->assertStatus(403);

    }

    /**
     * バリデーションを通過したデータは、DBに保存される
     */
    public function test_QaThreadController_store_succses(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Student)->first();
        $certification = Certification::first();

        $data = [
            'certification_id' => $certification->id,
            'title' => 'test',
            'body' => 'test',
            'user_id' => $user->id
        ];

        $response = $this->actingAs($user)
        ->post('/qa-board',$data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('qa_threads', [
            'title' => 'test',
            'body' => 'test',
        ]);

    }

    /**
     * バリデーションを通過しなかったデータはDBに保存されない
     */
    public function test_QaThreadController_store_error(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Student)->first();
        $certification = Certification::first();

        $data = [
            'certification_id' => '',
            'title' => '',
            'body' => '',
        ];

        $response = $this->actingAs($user)
        ->post('/qa-board',$data);

        $response->assertSessionHasErrors([
            'certification_id',
            'title',
            'body',
        ]);

    }

    /**
     * コーチは質問スレッドを作成できない
     */
    public function test_QaThreadController_store_Coach(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Coach)->first();

        $response = $this->actingAs($user)
        ->get('/qa-board/create');

        $response->assertStatus(403);

    }

    /**
     * 詳細画面を表示することができる
     */
    public function test_QaThreadController_show_Student(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Student)->first();
        $thread = QaThread::with('replies')->first();

        $response = $this->actingAs($user)
        ->get("/qa-board/{$thread->id}");

        $response->assertStatus(200);

        $response->assertviewIs('qa-thread.show');

        $response->assertSee($thread->title);
        $response->assertSee($thread->body);
        
        foreach ($thread->replies as $reply) {
            $response->assertSee($reply->body);
        }
    }

    public function test_QaThreadController_show_Coach(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Coach)->first();
        $thread = QaThread::with('replies')->first();

        $response = $this->actingAs($user)
        ->get("/qa-board/{$thread->id}");

        $response->assertStatus(200);

        $response->assertviewIs('qa-thread.show');

        $response->assertSee($thread->title);
        $response->assertSee($thread->body);
        
        foreach ($thread->replies as $reply) {
            $response->assertSee($reply->body);
        }
    }

    /**
     * 管理者も詳細画面を閲覧することができる
     */
    public function test_QaThreadController_show_Admin(): void
    {
        $this->seed();

        $user = User::where('role', UserRole::Admin)->first();
        $thread = QaThread::with('replies')->first();

        $response = $this->actingAs($user)
        ->get("/admin/qa-board/{$thread->id}");

        $response->assertStatus(200);

        $response->assertviewIs('qa-thread.show');

        $response->assertSee($thread->title);
        $response->assertSee($thread->body);
        
        foreach ($thread->replies as $reply) {
            $response->assertSee($reply->body);
        }
    }

    /**
     * 投稿者は編集画面が表示される
     */
    public function test_QaThreadController_edit_Student(): void
    {
        $this->seed();

        $thread = QaThread::with('replies')->first();
        $user = $thread->user;

        $response = $this->actingAs($user)
        ->get("/qa-board/{$thread->id}/edit");

        $response->assertStatus(200);

        $response->assertviewIs('qa-thread.edit');

        $response->assertSee($thread->title);
        $response->assertSee($thread->body);
    
    }

    /**
     * 投稿者以外は403
     */
    public function test_QaThreadController_edit_Student_403(): void
    {
        $this->seed();

        $thread = QaThread::with('replies')->first();
        $user = User::where('id', '!=', $thread->user_id)->first();

        $response = $this->actingAs($user)
        ->get("/qa-board/{$thread->id}/edit");

        $response->assertStatus(403);
    
    }

    /**
     * 投稿者は更新できる
     * バリデーションを通過したデータはDBに保存される
     */
    public function test_QaThreadController_update_Student(): void
    {
        $this->seed();

        $thread = QaThread::with('replies')->first();
        $user = $thread->user;

        $update_data = [
            'certification_id' => $thread->certification_id,
            'title' => 'test_edited',
            'body' => 'test body edited',
        ];

        $response = $this->actingAs($user)
        ->patch("/qa-board/{$thread->id}",$update_data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'title' => 'test_edited',
            'body' => 'test body edited',
        ]);
    
    }

    /**
     * 投稿者は回答がないスレッドは削除できる
     * DBからも削除される
     */
    public function test_QaThreadController_delete_success_Student(): void
    {
        $this->seed();

        $thread = QaThread::doesntHave('replies')->firstOrFail();
        $user = $thread->user;

        $response = $this->actingAs($user)
            ->delete("/qa-board/{$thread->id}");

        $response->assertStatus(302);

        $this->assertDatabaseMissing('qa_threads', [
            'id' => $thread->id,
        ]);
    }

    /**
     * 投稿者は回答があるスレッドは削除できない
     */
    public function test_QaThreadController_delete_error_Student(): void
    {
        $this->seed();

        $thread = QaThread::has('replies')->first();
        $user = $thread->user;

        $response = $this->actingAs($user)
        ->delete("/qa-board/{$thread->id}");

        $response->assertStatus(302);

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
        ]);
    
    }

    /**
     * 管理者は回答があってもスレッドを削除できる
     */
    public function test_QaThreadController_delete_success_Admin(): void
    {
        $this->seed();

        $thread = QaThread::whereHas('replies')->firstOrFail();
        $user = User::where('role', UserRole::Admin)->first();

        $response = $this->actingAs($user)
        ->delete("/admin/qa-board/{$thread->id}");

        $response->assertStatus(302);

        $this->assertDatabaseMissing('qa_threads', [
            'id' => $thread->id,
        ]);
    
    }

    /**
     * 投稿者は回答がある未解決スレッドを解決済みに変更できる
     * 
     */
    public function test_QaThreadController_resolved(): void
    {
        $this->seed();

        $thread = QaThread::with('replies')->where('status','open')->first();
        $user = $thread->user;

        $response = $this->actingAs($user)
        ->post("/qa-board/{$thread->id}/resolve");

        $response->assertStatus(302);

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'status' => 'resolved',
        ]);
    
    }

    /**
     * 投稿者はスレッドを未解決に変更できる
     */
    public function test_QaThreadController_open(): void
    {
        $this->seed();

        $thread = QaThread::with('replies')->where('status','resolved')->first();
        $user = $thread->user;

        $response = $this->actingAs($user)
        ->post("/qa-board/{$thread->id}/unresolve");

        $response->assertStatus(302);

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'status' => 'open',
        ]);
    
    }

}
