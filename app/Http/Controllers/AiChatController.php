<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AiChatConversation;
use App\Models\Message;
use App\Services\AiChatService;
use App\Enums\AiChatMessageStatus;
use App\Enums\AiChatMessageRole;


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
        return view('ai-chat.empty-state');
    }

    public function store(Request $request)
    {
        $this->authorize('access', AiChatConversation::class);
        $enrollment = auth()->user()->enrollments()->firstOrFail();
        $section = $enrollment
            ->certification
            ->parts
            ->flatMap(fn ($part) => $part->chapters)
            ->flatMap(fn ($chapter) => $chapter->sections)
            ->firstOrFail();

        $conversation = AiChatConversation::create([
            'user_id' => auth()->id(),
            'section_id' => $section->id,
            'title' => $request->input('title'),
        ]);

        return redirect()->route('ai-chat.show', $conversation);
    }

    public function show(AiChatConversation $conversation)
    {
        $this->authorize('owner', $conversation);
        $conversation->load('messages');
        return view('ai-chat.show', compact('conversation'));
    }

    public function update(AiChatConversation $conversation)
    {
        $this->authorize('owner', $conversation);
       
        return view('ai-chat.show', compact('conversation'));
    }

    public function destroy(AiChatConversation $conversation)
    {
        $this->authorize('owner', $conversation);
       
        return view('ai-chat.show', compact('conversation'));
    }

    public function messagestore(
    Request $request,
    AiChatConversation $conversation,
    AiChatService $aiChatService
) {
    $this->authorize('owner', $conversation);
    $content = $request->input('content');

    // ① 受講生のメッセージを保存
    $userMessage = $conversation->messages()->create([
        'content' => $content,
        'role' => 'user',
        'status' => AiChatMessageStatus::Pending->value,
    ]);

    // ② Geminiに質問を送る
    $aiResponse = $aiChatService->ask($content);

    // ③ AIの回答を保存
    $assistantMessage = $conversation->messages()->create([
        'content' => $aiResponse,
        'role' => 'assistant',
    ]);

    // ④ JavaScriptが受け取れるJSONを返す
    return response()->json([
        'user_message' => $userMessage,
        'assistant_message' => $assistantMessage,
        'conversation' => $conversation->fresh(),
    ]);
}
}
