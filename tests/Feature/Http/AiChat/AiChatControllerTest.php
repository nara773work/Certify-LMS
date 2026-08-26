<?php

namespace Tests\Feature\Http\AiChat;

use App\Enums\AiChatMessageRole;
use App\Models\AiChatConversation;
use App\Models\Message;
use App\Models\User;
use App\Services\AiChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiChatControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 学習中受講生はAI相談画面にアクセスできる
     */
    public function test_student_can_access_ai_chat(): void
    {
        $this->seed();

        $user = User::where(
            'email',
            'student@certify-lms.test'
        )->firstOrFail();

        $this->actingAs($user)
            ->get('/ai-chat')
            ->assertSuccessful();
    }

    /**
     * 会話を新規作成できる
     */
    public function test_can_create_conversation(): void
    {
        $this->seed();

        $user = User::where(
            'email',
            'student@certify-lms.test'
        )->firstOrFail();

        $this->actingAs($user)
            ->postJson('/ai-chat/conversations', [
                'message' => '',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('ai_chat_conversations', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * 会話詳細を表示できる
     */
    public function test_owner_can_view_conversation(): void
    {
        $this->seed();

        $user = User::where(
            'email',
            'student@certify-lms.test'
        )->firstOrFail();

        $conversation = AiChatConversation::create([
            'user_id' => $user->id,
            'title' => 'テスト相談',
            'last_message_at' => null,
            'section_id' => null,
            'enrollment_id' => null,
        ]);

        $this->actingAs($user)
            ->get("/ai-chat/conversations/{$conversation->id}")
            ->assertSuccessful();
    }

    /**
     * 過去の会話を再開できる
     */
    public function test_can_resume_previous_conversation(): void
    {
        $this->seed();

        $user = User::where(
            'email',
            'student@certify-lms.test'
        )->firstOrFail();

        $conversation = AiChatConversation::create([
            'user_id' => $user->id,
            'title' => '過去の相談',
            'last_message_at' => now(),
            'section_id' => null,
            'enrollment_id' => null,
        ]);

        Message::create([
            'ai_chat_conversation_id' => $conversation->id,
            'role' => AiChatMessageRole::User->value,
            'content' => '前の質問',
        ]);

        $this->actingAs($user)
            ->get("/ai-chat/conversations/{$conversation->id}")
            ->assertSuccessful()
            ->assertSee('前の質問');
    }

    /**
     * タイトルを編集できる
     */
    public function test_owner_can_update_title(): void
    {
        $this->seed();

        $user = User::where(
            'email',
            'student@certify-lms.test'
        )->firstOrFail();

        $conversation = AiChatConversation::create([
            'user_id' => $user->id,
            'title' => '新しい相談',
            'last_message_at' => null,
            'section_id' => null,
            'enrollment_id' => null,
        ]);

        $this->actingAs($user)
            ->patch(
                "/ai-chat/conversations/{$conversation->id}",
                [
                    'title' => 'Laravelについて相談',
                ]
            )
            ->assertRedirect();

        $this->assertDatabaseHas('ai_chat_conversations', [
            'id' => $conversation->id,
            'title' => 'Laravelについて相談',
        ]);
    }

    /**
     * 会話を削除できる
     */
    public function test_owner_can_delete_conversation(): void
    {
        $this->seed();

        $user = User::where(
            'email',
            'student@certify-lms.test'
        )->firstOrFail();

        $conversation = AiChatConversation::create([
            'user_id' => $user->id,
            'title' => '削除する相談',
            'last_message_at' => null,
            'section_id' => null,
            'enrollment_id' => null,
        ]);

        $this->actingAs($user)
            ->delete("/ai-chat/conversations/{$conversation->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('ai_chat_conversations', [
            'id' => $conversation->id,
        ]);
    }

    /**
     * メッセージを送信してAI回答を保存できる
     */
    public function test_can_send_message_and_save_ai_response(): void
    {
        $this->seed();

        $user = User::where(
            'email',
            'student@certify-lms.test'
        )->firstOrFail();

        $conversation = AiChatConversation::create([
            'user_id' => $user->id,
            'title' => 'AI相談テスト',
            'last_message_at' => null,
            'section_id' => null,
            'enrollment_id' => null,
        ]);

        $this->mock(AiChatService::class, function ($mock) {
            $mock->shouldReceive('ask')
                ->once()
                ->andReturn('AIからの回答です');
        });

        $this->actingAs($user)
            ->postJson(
                "/ai-chat/conversations/{$conversation->id}/messages",
                [
                    'content' => 'Laravelについて教えて',
                ]
            )
            ->assertSuccessful();

        $this->assertDatabaseHas('messages', [
            'ai_chat_conversation_id' => $conversation->id,
            'content' => 'Laravelについて教えて',
            'role' => AiChatMessageRole::User->value,
        ]);

        $this->assertDatabaseHas('messages', [
            'ai_chat_conversation_id' => $conversation->id,
            'content' => 'AIからの回答です',
            'role' => AiChatMessageRole::Assistant->value,
        ]);
    }

    /**
     * AI失敗時もユーザーの質問は保存される
     */
    public function test_message_remains_when_ai_fails(): void
    {
        $this->seed();

        $user = User::where(
            'email',
            'student@certify-lms.test'
        )->firstOrFail();

        $conversation = AiChatConversation::create([
            'user_id' => $user->id,
            'title' => 'AI失敗テスト',
            'last_message_at' => null,
            'section_id' => null,
            'enrollment_id' => null,
        ]);

        $this->mock(AiChatService::class, function ($mock) {
            $mock->shouldReceive('ask')
                ->once()
                ->andThrow(
                    new \RuntimeException('Gemini API error')
                );
        });

        $this->actingAs($user)
            ->postJson(
                "/ai-chat/conversations/{$conversation->id}/messages",
                [
                    'content' => '再質問したい内容',
                ]
            )
            ->assertServerError();

        $this->assertDatabaseHas('messages', [
            'ai_chat_conversation_id' => $conversation->id,
            'content' => '再質問したい内容',
            'role' => AiChatMessageRole::User->value,
        ]);
    }

    /**
     * AI失敗後に同じ会話へ再質問できる
     */
    public function test_can_retry_message_after_ai_failure(): void
    {
        $this->seed();

        $user = User::where(
            'email',
            'student@certify-lms.test'
        )->firstOrFail();

        $conversation = AiChatConversation::create([
            'user_id' => $user->id,
            'title' => '再質問テスト',
            'last_message_at' => null,
            'section_id' => null,
            'enrollment_id' => null,
        ]);

        $this->mock(AiChatService::class, function ($mock) {
            $mock->shouldReceive('ask')
                ->once()
                ->andReturn('再質問への回答');
        });

        $this->actingAs($user)
            ->postJson(
                "/ai-chat/conversations/{$conversation->id}/messages",
                [
                    'content' => 'もう一度質問します',
                ]
            )
            ->assertSuccessful();

        $this->assertDatabaseHas('messages', [
            'ai_chat_conversation_id' => $conversation->id,
            'content' => '再質問への回答',
            'role' => AiChatMessageRole::Assistant->value,
        ]);
    }

    /**
     * 他のユーザーは会話を表示できない
     */
    public function test_other_user_cannot_view_conversation(): void
    {
        $this->seed();

        $owner = User::where(
            'email',
            'student@certify-lms.test'
        )->firstOrFail();

        $other = User::where(
            'email',
            'student-noquota@certify-lms.test'
        )->firstOrFail();

        $conversation = AiChatConversation::create([
            'user_id' => $owner->id,
            'title' => 'オーナー専用',
            'last_message_at' => null,
            'section_id' => null,
            'enrollment_id' => null,
        ]);

        $this->actingAs($other)
            ->get("/ai-chat/conversations/{$conversation->id}")
            ->assertForbidden();
    }

    /**
     * 他のユーザーは会話を削除できない
     */
    public function test_other_user_cannot_delete_conversation(): void
    {
        $this->seed();

        $owner = User::where(
            'email',
            'student@certify-lms.test'
        )->firstOrFail();

        $other = User::where(
            'email',
            'student-noquota@certify-lms.test'
        )->firstOrFail();

        $conversation = AiChatConversation::create([
            'user_id' => $owner->id,
            'title' => 'オーナー専用',
            'last_message_at' => null,
            'section_id' => null,
            'enrollment_id' => null,
        ]);

        $this->actingAs($other)
            ->delete("/ai-chat/conversations/{$conversation->id}")
            ->assertForbidden();
    }

    /**
     * 他のユーザーはメッセージを送信できない
     */
    public function test_other_user_cannot_send_message(): void
    {
        $this->seed();

        $owner = User::where(
            'email',
            'student@certify-lms.test'
        )->firstOrFail();

        $other = User::where(
            'email',
            'student-noquota@certify-lms.test'
        )->firstOrFail();

        $conversation = AiChatConversation::create([
            'user_id' => $owner->id,
            'title' => 'オーナー専用',
            'last_message_at' => null,
            'section_id' => null,
            'enrollment_id' => null,
        ]);

        $this->actingAs($other)
            ->postJson(
                "/ai-chat/conversations/{$conversation->id}/messages",
                [
                    'content' => '不正アクセス',
                ]
            )
            ->assertForbidden();
    }

    /**
     * メッセージ文字数制限
     */
    public function test_message_has_max_length(): void
    {
        $this->seed();

        $user = User::where(
            'email',
            'student@certify-lms.test'
        )->firstOrFail();

        $conversation = AiChatConversation::create([
            'user_id' => $user->id,
            'title' => '文字数テスト',
            'last_message_at' => null,
            'section_id' => null,
            'enrollment_id' => null,
        ]);

        $this->actingAs($user)
            ->postJson(
                "/ai-chat/conversations/{$conversation->id}/messages",
                [
                    'content' => str_repeat('a', 2001),
                ]
            )
            ->assertStatus(422);
    }

    /**
     * タイトル文字数制限
     */
    public function test_title_has_max_length(): void
    {
        $this->seed();

        $user = User::where(
            'email',
            'student@certify-lms.test'
        )->firstOrFail();

        $conversation = AiChatConversation::create([
            'user_id' => $user->id,
            'title' => '文字数テスト',
            'last_message_at' => null,
            'section_id' => null,
            'enrollment_id' => null,
        ]);

        $this->actingAs($user)
            ->patchJson(
                "/ai-chat/conversations/{$conversation->id}",
                [
                    'title' => str_repeat('a', 201),
                ]
            )
            ->assertStatus(422);
    }
}