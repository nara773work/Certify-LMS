<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\MeetingPack;
use App\Enums\MeetingPackStatus;
use App\Http\Requests\Meeting\MeetingPackRequest;

class MeetingPackController extends Controller
{
    public function index(Request $request){
        $this->authorize('view', MeetingPack::class);

        $query = MeetingPack::query();
        $keyword = $request->input('keyword');
        $status = $request->input('status');

        if($keyword){
            $query->where('name','like',"%{$keyword}%");
        }

        if($status){
            $query->where('status',$status);
        }

        $plans = $query->orderBy('sort_order','ASC')->paginate(10);

        return view('meeting-pack.management.index',compact('plans','keyword','status'));
    }

    public function create(){

        $this->authorize('view', MeetingPack::class);

        return view('meeting-pack.management.create');
    }

    public function store(MeetingPackRequest $request){

        $user = Auth()->user();

        $plan = MeetingPack::create([
            'name' => $request->name,
            'description' => $request->description,
            'meeting_count' => $request->meeting_count,
            'price' => $request->price,
            'stripe_price_id' => $request->stripe_price_id,
            'sort_order' => $request->sort_order,
            'status' => MeetingPackStatus::Draft,
            'created_by_user_id' => $user->id,
            'updated_by_user_id' => $user->id
        ]);

        return redirect()->route('admin.meeting-packs.index')->with('success','SKUを作成しました');
    }

    public function show(MeetingPack $plan){

        $this->authorize('view', $plan);

        $plan->load([
            'payments' => function ($query) {
                $query->latest('paid_at')->take(20);
        },
            'payments.user',
        ]);


        return view('meeting-pack.management.show',compact('plan'));
    }

    public function edit(MeetingPack $plan){

        $this->authorize('update', $plan);

        return view('meeting-pack.management.edit',compact('plan'));
    }

    public function update(MeetingPackRequest $request,MeetingPack $plan){

        $this->authorize('update', $plan);

        $user = Auth()->user();

        $plan->update([
            'name' => $request->name,
            'description' => $request->description,
            'meeting_count' => $request->meeting_count,
            'price' => $request->price,
            'stripe_price_id' => $request->stripe_price_id,
            'sort_order' => $request->sort_order,
            'status' => MeetingPackStatus::Draft,
            'created_by_user_id' => $user->id,
            'updated_by_user_id' => $user->id
        ]);

        return redirect()->route('admin.meeting-packs.index')->with('success','SKUを更新しました');
    }

    public function destroy(MeetingPack $plan){

        $this->authorize('delete', $plan);

        if($plan->status === MeetingPackStatus::Published){
            return redirect()->route('admin.meeting-packs.index')->with('error','公開中のため削除できません');
        }
        $plan->delete();

        return redirect()->route('admin.meeting-packs.index')->with('success','SKUを削除しました');
    }

    public function publish(MeetingPack $plan){

        $this->authorize('publish', $plan);

        $plan->update([
            'status'=> MeetingPackStatus::Published,
        ]);

        return redirect()->route('admin.meeting-packs.index')->with('success','SKUを公開しました');
    }

    public function archive(MeetingPack $plan){

        $this->authorize('archive', $plan);

        $plan->update([
            'status'=> MeetingPackStatus::Archived,
        ]);

        return redirect()->route('admin.meeting-packs.index')->with('success','アーカイブにしました');
    }

    public function unarchive(MeetingPack $plan){

        $this->authorize('unarchive', $plan);

        $plan->update([
            'status'=> MeetingPackStatus::Draft,
        ]);

        return redirect()->route('admin.meeting-packs.index')->with('success','下書きにしました');
    }
}
