<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\MeetingPack;
use App\Models\Payment;
use App\Models\User;
use App\Enums\PaymentStatus;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $meetingPack = MeetingPack::first();
        $user = User::where('role', \App\Enums\UserRole::Student)->first();

        Payment::create([
            'meeting_pack_id' => $meetingPack->id,
            'user_id' => $user->id,
            'amount' => $meetingPack->price,
            'quantity' => $meetingPack->meeting_count,
            'status' => PaymentStatus::Succeeded,
            'paid_at' => Carbon::today()->subDays(10)
        ]);

    }
}
