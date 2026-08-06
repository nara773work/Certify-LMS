<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class QaReplay extends Controller
{
    /**
     * 質問スレッド投稿画面
     */
    public function create(){
       
        $certifications = Certification::all();

        return view('qa-thread.create',compact('certifications'));

    }

    /**
     * 質問スレッド保存処理を行う
     */

    public function store(Request $request){

        $thread = QaReplay::create([
            'certification_id' => $request->certification_id,
            'title' => $request->title,
            'body' => $request->body,
        ]);

        return redirect()->route('qa-board.index');
    }

    /**
     * 質問詳細画面を表示する
     */
    public function show(QaThread $thread){

        $replies = $thread->replies;

        return view('qa-thread.show',compact('thread','replies'));

    }

    public function edit(){

        return view('qa-thread.edit');
    }

    public function patch(QaThread $thread){

        $thread = QaThread::patch([
            'certification_id' => $request->certification_id,
            'title' => $request->title,
            'body' => $request->body,
        ]);

        return view('qa-thread.edit');
    }

    public function destroy(QaThread $thread){

        $thread = delete();

        return view('qa-thread.destroy');
    }
}
