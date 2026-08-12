<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EnrollmentGoal;
use App\Models\Enrollment;
use App\Http\Requests\Enrollment\EnrollmentRequest;

class EnrollmentGoalController extends Controller
{
    public function store(EnrollmentRequest $request,Enrollment $enrollment){

        $this->authorize('create', [EnrollmentGoal::class, $enrollment]);

        $goal = EnrollmentGoal::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => auth()->id(),
            'title' => $request->title, 
            'target_date' => $request->target_date, 
            'description' => $request->description, 
            'achieved_at' => null,
        ]);

        return redirect()->route('enrollments.show', $enrollment)
        ->with('success','個人目標を作成しました');
    }

    public function edit(EnrollmentGoal $goal){

        $this->authorize('update', $goal);

        return view('enrollment-goal.edit',compact('goal'));

    }

    public function update(EnrollmentRequest $request,EnrollmentGoal $goal){

        $this->authorize('update',$goal);

        $goal->update([
            'title' => $request->title,
            'target_date' =>$request->target_date,
            'description' =>$request->description,
        ]);

        return redirect()
        ->route('enrollments.show', [ 'enrollment' => $goal->enrollment->id, ])
        ->with('success', '個人目標を更新しました');

    }

    public function destroy(EnrollmentGoal $goal){

        $this->authorize('delete', $goal);

        $goal->delete();

        return back()->with('success','個人目標を削除しました');

    }

    public function achieve(EnrollmentGoal $goal){

        $this->authorize('markAchieved', $goal);

        $goal->update([
            'achieved_at' => now(), 
        ]);

        return back()->with('success','個人目標を達成しました');
    }

        public function unachieve(EnrollmentGoal $goal){

        $this->authorize('markAchieved', $goal);

        $goal->update([
            'achieved_at' => null, 
        ]);

        return back()->with('success','個人目標を未達成に戻しました');
    }
}
