<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Certification;
use App\Models\QaThread;
use App\Models\Reply;
use App\Enums\QaThreadStatus;
use App\Enums\CertificationStatus;

class QaThreadController extends Controller
{
    /**
     * 質問掲示板の一覧表示画面
     * filterで条件を絞ることが可能であり
     * 解決・未解決、試験別、本文に含まれるキーワードで検索することができる
     * ページネーションで取得しており、ページを跨いでも検索結果は保持される
    */
    public function index(Request $request){

        $user = auth()->user(); 
        $indexRoute = 'qa-board.index';

        $certifications = Certification::where('status',CertificationStatus::Published)
        ->get();
        
        $publishedStatus = CertificationStatus::Published;

        $query = QaThread::query();

        //フィルター
        $filters = $request->input('filters', [
            'status' => $request->input('status', ''),
            'certification_id' => $request->input('certification_id'),
            'keyword' => $request->input('keyword'),
        ]);

        if($filters['status'] == 'unresolved'){
            $query = QaThread::where('status','unresolved');
        }
        elseif($filters['status'] == 'resolved'){
            $query = QaThread::where('status','resolved');
        }

        if (!empty($filters['certification_id'])) {
            $query->where('certification_id', $filters['certification_id']);
        }

        if(!empty($filters['keyword'])){
            $query->where('body','like','%'.$filters['keyword'].'%');
        }

        $threads = $query
        ->with('certification')->with('user')->paginate(10)->withQueryString();

        return view('qa-thread.index',
        compact('filters','certifications','indexRoute','publishedStatus','threads'));

    }

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

        $thread = QaThread::create([
            'certification_id' => $request->certification_id,
            'title' => $request->title,
            'body' => $request->body,
            'user_id' => auth()->id(),
            'status' => QaThreadStatus::UnResolved,
        ]);

        return redirect()->route('qa-board.index')->with('success', '質問を作成しました。');
    }

    /**
     * 質問詳細画面を表示する
     */
    public function show(QaThread $thread){

        $destroyRoute = 'qa-board.destroy';

        $replies = $thread->replies;

        return view('qa-thread.show',compact('thread','replies','destroyRoute'));

    }

    public function edit(QaThread $thread){

        return view('qa-thread.edit',compact('thread'));
    }

    public function update(QaThread $thread, Request $request){

        $thread->update([
            'title' => $request->title,
            'body' => $request->body,
        ]);

        return redirect()->route('qa-board.show',$thread)->with('success', '質問を更新しました。');
    }

    public function destroy(QaThread $thread){

        if ($thread->replies()->exists()) {

            return back()->with('error', '紐づいている回答があるため削除できません。');

        }
        
        $thread->delete();

        return redirect()->route('qa-board.index')
        ->with('success', '質問を削除しました。');

    }

    public function resolve(QaThread $thread)
    {
        $thread->update([
            'status' => QaThreadStatus::Resolved,
        ]);

        return back();
    }

    public function unresolve(QaThread $thread)
    {
        $thread->update([
            'status' => QaThreadStatus::UnResolved,
        ]);

        return back();
    }

}
