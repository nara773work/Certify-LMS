<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EnrollmentNote;
use App\Models\Enrollment;

class EnrollmentNoteController extends Controller
{
    public function store( Enrollment $enrollment,Request $request){
        $this->authorize('create', [EnrollmentNote::class, $enrollment]);

        $note = EnrollmentNote::create([
            'body'=>$request->body,
            'user_id'=>auth()->id(),
            'enrollment_id'=>$enrollment->id
        ]);

        return redirect()->route('enrollments.show',$enrollment);
    }

    public function edit(EnrollmentNote $note){
        $this->authorize('edit', $note);

        return view('enrollment-note.edit',compact('note'));
    }

    public function update(Request $request,EnrollmentNote $note){
        $this->authorize('update', $note);
        $enrollment = $note->enrollment;

        $note->update([
            'body'=>$request->body,
        ]);

        return redirect()->route('enrollments.show', $enrollment);
    }

    public function destroy(EnrollmentNote $note){
        $this->authorize('delete', $note);
        $enrollment = $note->enrollment;

        $note->delete();

        return redirect()->route('enrollments.show', $enrollment);
    }
}
