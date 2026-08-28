<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\Payment;
use App\Models\MeetingPack;

class MeetingQuotaController extends Controller
{
    public function index()
    {
        $plans = Plan::all();
       return view('meeting-quota.checkout-select',compact('plans'));
    }

    public function store(Request $request)
    {
        return redirect()->route('meeting-quota.success');
    }

    public function success()
    {
        $payment = Payment::where('user_id', auth()->user()->id)->first();
        $meetingPack = MeetingPack::where('status', 'published')->first();
        return view('meeting-quota.success',compact('payment','meetingPack'));
    }
}
