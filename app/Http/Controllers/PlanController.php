<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PlanStatus;
use App\Http\Requests\Plan\PlanRequest;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index(Request $request)
    {

        $this->authorize('viewAny', Plan::class);

        $query = Plan::query();
        $keyword = $request->input('keyword');
        $status = $request->input('status');

        if ($keyword) {
            $query->where('name', 'like', "%{$keyword}%");
        }

        if ($status) {
            $query->where('status', $status);
        }

        $plans = $query->withCount('users')->paginate(10);

        return view('plan.management.index', compact('plans', 'keyword', 'status'));
    }

    public function show(Plan $plan)
    {

        $this->authorize('view', $plan);

        return view('plan.management.show', compact('plan'));
    }

    public function create(Plan $plan)
    {

        $this->authorize('create', $plan);

        return view('plan.management.create', compact('plan'));
    }

    public function store(Plan $plan, PlanRequest $request)
    {

        $this->authorize('create', $plan);

        $user = Auth()->user();

        $plan = Plan::create([
            'name' => $request->name,
            'description' => $request->description,
            'duration_days' => $request->duration_days,
            'default_meeting_quota' => $request->default_meeting_quota,
            'sort_order' => $request->sort_order,
            'created_by_user_id' => $user->id,
            'updated_by_user_id' => $user->id,
        ]);

        return redirect()->route('admin.plans.index')->with('success', 'プランを作成しました');
    }

    public function edit(Plan $plan)
    {

        $this->authorize('update', $plan);

        return view('plan.management.edit', compact('plan'));
    }

    public function update(Plan $plan, PlanRequest $request)
    {

        $this->authorize('update', $plan);

        $plan->update([
            'name' => $request->name,
            'description' => $request->description,
            'duration_days' => $request->duration_days,
            'default_meeting_quota' => $request->default_meeting_quota,
            'sort_order' => $request->sort_order,
        ]);

        return redirect()->route('admin.plans.show', $plan)->with('success', 'プランを更新しました');
    }

    public function destroy(Plan $plan)
    {

        $this->authorize('delete', $plan);

        if ($plan->status === PlanStatus::Published) {
            return redirect()->route('admin.plans.index')->with('error', '公開中のため削除できません');
        }
        if ($plan->userPlanLogs()->exists()) {
            return redirect()
                ->route('admin.plans.index')
                ->with('error', '利用履歴があるため削除できません');
        }
        $plan->delete();

        return redirect()->route('admin.plans.index')->with('success', 'プランを削除しました');
    }

    public function publish(Plan $plan)
    {

        $this->authorize('publish', $plan);

        $plan->update([
            'status' => PlanStatus::Published,
        ]);

        return redirect()->route('admin.plans.index')->with('success', '公開しました');
    }

    public function archive(Plan $plan)
    {

        $this->authorize('archive', $plan);

        $plan->update([
            'status' => PlanStatus::Archived,
        ]);

        return redirect()->route('admin.plans.index')->with('success', 'アーカイブにしました');
    }

    public function unarchive(Plan $plan)
    {

        $this->authorize('unarchive', $plan);

        $plan->update([
            'status' => PlanStatus::Draft,
        ]);

        return redirect()->route('admin.plans.index')->with('success', '下書きにしました');
    }
}
