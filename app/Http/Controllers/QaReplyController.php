<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Http\Requests\QaBoard\QaReplyRequest;

class QaReplyController extends Controller
{
    /**
     * 質問スレッド保存処理を行う
     */

    public function store(QaReplyRequest $request, QaThread $thread){
        $this->authorize('create', QaReply::class);

        $reply = QaReply::create([
            'body' => $request->body,
            'qa_thread_id' => $thread->id,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('qa-board.show',$thread)->with('success', '回答を作成しました。');
    }

    public function edit(QaReply $reply){
        $this->authorize('edit', $reply);

        return view('qa-thread.reply-edit',compact('thread','reply'));
    }

    public function update(QaReplyRequest $request,QaThread $thread,QaReply $reply){
        $this->authorize('update', $reply);

        $reply->update([
            'body' => $request->body,
        ]);

        return redirect()->route('qa-board.index')->with('success', '回答を更新しました。');
    }

    public function destroy(QaThread $thread,QaReply $reply){
        $this->authorize('delete', $reply);

        $reply->delete();

        return redirect()->route('qa-board.show',$thread)->with('success', '回答を削除しました。');
    }
}
