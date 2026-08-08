<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Certification;
use App\Models\QaThread;
use App\Models\QaReply;
use App\Enums\QaThreadStatus;
use App\Enums\CertificationStatus;
use App\Enums\UserRole;
use App\Http\Requests\QaBoard\QaThreadRequest;

class QaThreadController extends Controller
{
    /**
     * 質問掲示板の一覧表示画面
     * filterで条件を絞ることが可能であり
     * 解決・未解決、試験別、本文に含まれるキーワードで検索することができる
     * ページネーションで取得しており、ページを跨いでも検索結果は保持される
    */
    public function index(Request $request,){
        $this->authorize('viewAny', QaThread::class);

        $user = auth()->user(); 
        $indexRoute = 'qa-board.index';

        $certifications = Certification::where('status',CertificationStatus::Published)
        ->get();
        
        $publishedStatus = CertificationStatus::cases();

        $query = QaThread::query();

        if ($user->role === UserRole::Coach) {
            $query->whereIn(
                'certification_id',$user->coachingCertificationIds()
            );
        }

        if (auth()->user()->role === UserRole::Admin) {
            $certifications = Certification::all();
        } else {
            $certifications = Certification::where(
            'status',
            CertificationStatus::Published
            )->get();
    }

        //フィルター
        $filters = $request->input('filters', [
            'status' => $request->input('status', ''),
            'certification_id' => $request->input('certification_id'),
            'keyword' => $request->input('keyword'),
        ]);

        if ($filters['status'] == 'unresolved') {
            $query->where('status', QaThreadStatus::UnResolved);
        } elseif ($filters['status'] == 'resolved') {
            $query->where('status', QaThreadStatus::Resolved);
        }

        if (!empty($filters['certification_id'])) {
            $query->where('certification_id', $filters['certification_id']);
        }

        if(!empty($filters['keyword'])){
            $query->where('body','like','%'.$filters['keyword'].'%');
        }

        $threads = $query
        ->with('certification')
        ->with('user')
        ->withCount('replies')
        ->orderBy('created_at','desc')
        ->paginate(10)
        ->withQueryString();

        return view('qa-thread.index',
        compact('filters','certifications','indexRoute','publishedStatus','threads'));

    }

    /**
     * 質問スレッド投稿画面
     */
    public function create(){
        $this->authorize('create', QaThread::class);
       
        $certifications = Certification::where('status',CertificationStatus::Published);

        return view('qa-thread.create',compact('certifications'));

    }

    /**
     * 質問スレッド保存処理を行う
     */

    public function store(QaThreadRequest $request){
        $this->authorize('update', QaThread::class);

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
        $this->authorize('view', $thread);

        $user = auth()->user();
        $thread->load(['replies.user']);
        $thread->loadCount('replies');

        if($user->role === UserRole::Admin){
            $destroyRoute = 'admin.qa-board.destroy';
        
        }else{
            $destroyRoute = 'qa-board.destroy';
        }

        return view('qa-thread.show',compact('thread','destroyRoute'));

    }

    /**
     * 質問編集画面を表示する
     */
    public function edit(QaThread $thread){
        $this->authorize('edit', $thread);

        return view('qa-thread.edit',compact('thread'));
    }

    /**
     * 質問を更新する
     */
    public function update(QaThreadRequest $request,QaThread $thread){
        $this->authorize('update', $thread);

        $thread->update([
            'title' => $request->title,
            'body' => $request->body,
        ]);

        return redirect()->route('qa-board.show',$thread)->with('success', '質問を更新しました。');
    }

    /**
     * 質問を削除する
     * 紐づいている回答がある場合は削除できない
     */
    public function destroy(QaThread $thread){
        $this->authorize('delete', $thread);

        $user = Auth()->user();

        if ($thread->replies()->exists()) {

            if($user->role !== UserRole::Admin){
                return back()->with('error', '紐づいている回答があるため削除できません。');
            } 

            $thread->delete();

            return redirect()->route('admin.qa-board.index',$thread)->with('success', '質問を削除しました。');

        }
        
        $thread->delete();

        return redirect()->route('qa-board.index')
        ->with('success', '質問を削除しました。');

    }

    /**
     * 質問スレッドのステータスを解決済みに更新する
     * 回答数が0のまま解決済みにはできないようにする
     */
    public function resolve(QaThread $thread){
        $this->authorize('resolve', $thread);

        if(! $thread->replies()->exists()){
            return back()->with('error', '回答がないため解決済みにできません');
        }

        $thread->update([
            'status' => QaThreadStatus::Resolved,
        ]);

        return back()->with('success', '解決済みに変更しました');
    }

    /**
     * 質問スレッドのステータスを未解決に更新する
     */
    public function unresolve(QaThread $thread){
        $this->authorize('unresolve', $thread);

        $thread->update([
            'status' => QaThreadStatus::UnResolved,
        ]);

        return back()->with('success', '未解決に変更しました');
    }

}
