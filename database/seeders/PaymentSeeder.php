<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PaymentStatus;
use App\Models\MeetingPack;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $student = User::query()
            ->where('role', 'student')
            ->orderBy('created_at')
            ->first();

        $meetingPack = MeetingPack::query()
            ->where('status', 'published')
            ->orderBy('sort_order')
            ->first();

        if ($student === null || $meetingPack === null) {
            $this->command?->warn(
                'PaymentSeeder: student または published MeetingPack が存在しません。'
            );

            return;
        }

        Payment::create([
            'meeting_pack_id' => $meetingPack->id,
            'user_id' => $student->id,
            'amount' => $meetingPack->price,
            'quantity' => $meetingPack->meeting_count,
            'status' => PaymentStatus::Succeeded,
            'paid_at' => now(),
        ]);
    }
}