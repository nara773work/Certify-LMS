<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AiChatConversation;
use App\Models\Message;
use App\Services\AiChatService;
use App\Enums\AiChatMessageStatus;
use App\Enums\AiChatMessageRole;
use App\Models\Section;
use App\Enums\EnrollmentStatus;
use App\Http\Requests\AiChatRequest;

class AiChatController extends Controller
{
    /**
     * AIチャット画面を表示する
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $this->authorize('access', AiChatConversation::class);

        $conversation = auth()->user()
        ->aiChatConversations()
        ->orderByDesc('last_message_at')
        ->first();

        if ($conversation) {
            return redirect()->route(
                'ai-chat.conversations.show',
                $conversation
            );
        }
        return view('ai-chat.empty-state');
    }

    public function store(Request $request, AiChatService $aiChatService)
{
    $this->authorize('access', AiChatConversation::class);

    // section_id がある場合だけ Section を取得
    $section = !empty($request->input('section_id'))
        ? Section::with('chapter.part.certification')->find($request->input('section_id'))
        : null;

    // Section がある場合だけ資格を取得
    $certification = $section?->chapter?->part?->certification;

    // 資格が分かる場合、その資格の受講情報を取得
    $enrollment = null;

    if ($certification) {
        $enrollment = auth()->user()
            ->enrollments()
            ->where('certification_id', $certification->id)
            ->whereIn('status', [
                \App\Enums\EnrollmentStatus::Learning,
                \App\Enums\EnrollmentStatus::Passed,
            ])
            ->first();
    }

    // Section がない場合は既定の受講資格をフォールバック
    if (!$enrollment) {
        $enrollment = auth()->user()->defaultEnrollment;
    }

    $conversation = AiChatConversation::create([
        'user_id' => auth()->id(),
        'section_id' => $section?->id,
        'enrollment_id' => $enrollment?->id,
        'title' => '新しい相談',
    ]);

    $content = trim((string) $request->input('message'));

    if ($content !== '') {
        $userMessage = $conversation->messages()->create([
            'content' => $content,
            'role' => AiChatMessageRole::User->value,
            'status' => AiChatMessageStatus::Pending->value,
        ]);

        try {
            $aiResponse = $aiChatService->ask($content, $section);

            $assistantMessage = $conversation->messages()->create([
                'content' => $aiResponse,
                'role' => AiChatMessageRole::Assistant->value,
                'status' => AiChatMessageStatus::Completed->value,
            ]);

            $userMessage->update([
                'status' => AiChatMessageStatus::Completed->value,
            ]);

            $conversation->update([
                'last_message_at' => now(),
            ]);

        } catch (\Throwable $e) {
            $userMessage->update([
                'status' => AiChatMessageStatus::Error->value,
            ]);

            throw $e;
        }
    }

    if ($request->expectsJson()) {
    return response()->json([
        'conversation' => $conversation,
    ], 201);
}

    return redirect()->route(
        'ai-chat.conversations.show',
        $conversation
    );
    }

    public function show(AiChatConversation $conversation)
    {
        $this->authorize('owner', $conversation);
        $conversation->load('messages');
        return view('ai-chat.show', compact('conversation'));
    }

    public function update(AiChatRequest $request, AiChatConversation $conversation)
    {
         $this->authorize('owner', $conversation);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
        ]);

        $conversation->update([
            'title' => $validated['title'],
        ]);

        return redirect()->route(
            'ai-chat.conversations.show',
            $conversation
        );
    }

    public function destroy(AiChatConversation $conversation)
    {
        $this->authorize('owner', $conversation);

        $conversation->delete();

        return redirect()->route('ai-chat.index');
    }

    public function messagestore(
    Request $request,
    AiChatConversation $conversation,
    AiChatService $aiChatService
) {
    $this->authorize('owner', $conversation);

    $content = trim((string) $request->input('content'));

    // この会話に紐づいているSection
    $section = $conversation->section;

    // ① 受講生のメッセージを保存
    $userMessage = $conversation->messages()->create([
        'content' => $content,
        'role' => AiChatMessageRole::User->value,
        'status' => AiChatMessageStatus::Pending->value,
    ]);

    try {
        // ② 質問 + Section情報をGeminiへ送る
        $aiResponse = $aiChatService->ask($content, $section);

        // ③ AIの回答を保存
        $assistantMessage = $conversation->messages()->create([
            'content' => $aiResponse,
            'role' => AiChatMessageRole::Assistant->value,
            'status' => AiChatMessageStatus::Completed->value,
        ]);

        // ④ ユーザーの質問も成功扱い
        $userMessage->update([
            'status' => AiChatMessageStatus::Completed->value,
        ]);

        return response()->json([
            'user_message' => $userMessage,
            'assistant_message' => $assistantMessage,
            'conversation' => $conversation->fresh(),
        ]);

    } catch (\Throwable $e) {

        // AI失敗でも質問は履歴に残す
        $userMessage->update([
            'status' => AiChatMessageStatus::Error->value,
        ]);

        throw $e;
    }
}
}