<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QaReply;
use App\Models\QaThread;

class QaReplyController extends Controller
{
    /**
     * 質問スレッド保存処理を行う
     */

    public function store(Request $request, QaThread $thread){

        $reply = QaReply::create([
            'body' => $request->body,
            'qa_thread_id' => $thread->id,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('qa-board.show',$thread)->with('success', '回答を作成しました。');
    }

    public function edit(QaThread $thread,QaReply $reply){

        return view('qa-thread.reply-edit',compact('thread','reply'));
    }

    public function update(Request $request,QaThread $thread,QaReply $reply){

        $reply->update([
            'body' => $request->body,
        ]);

        return redirect()->route('qa-board.index')->with('success', '回答を更新しました。');
    }

    public function destroy(QaThread $thread,QaReply $reply){

        $reply->delete();

        return redirect()->route('qa-board.show',$thread)->with('success', '回答を削除しました。');
    }
}
