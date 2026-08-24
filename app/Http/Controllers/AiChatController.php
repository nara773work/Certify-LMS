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
        $conversation = Conversation::create([
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('ai-chat.conversations.show', $conversation);
    }
}
