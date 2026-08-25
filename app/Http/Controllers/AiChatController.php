<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\AiChat;

class AiChatController extends Controller
{
    /**
     * AIチャット画面を表示する
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('ai-chat.empty-state');
    }

    public function store(Request $request)
    {
        $enrollment = auth()->user()->enrollments()->firstOrFail();
        $section = $enrollment
            ->certification
            ->parts
            ->flatMap(fn ($part) => $part->chapters)
            ->flatMap(fn ($chapter) => $chapter->sections)
            ->firstOrFail();

        $conversation = Conversation::create([
            'user_id' => auth()->id(),
            'enrollment_id' => $enrollment->id,
            'section_id' => $section->id,
        ]);

        return redirect()->route('ai-chat.conversations.show', $conversation);
    }

    public function show(Conversation $conversation)
    {
       
        return view('ai-chat.show', compact('conversation'));
    }

}
