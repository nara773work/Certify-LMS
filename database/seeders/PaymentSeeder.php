<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PaymentStatus;
use App\Enums\UserStatus;
use App\Models\MeetingPack;
use App\Models\MeetingQuotaTransaction;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $student = User::query()
            ->where('role', 'student')
            ->where('email', 'student@certify-lms.test')
            ->orderBy('created_at')
            ->first();

        $demoStudent = User::query()
            ->where('role', 'student')
            ->where('status', UserStatus::InProgress)
            ->orderBy('created_at')
            ->first();

        if ($student === null || $demoStudent === null) {
            $this->command?->warn(
                'PaymentSeeder: 固定受講生またはデモ受講生が存在しません。'
            );

            return;
        }

        $meetingPacks = MeetingPack::query()
            ->where('status', 'published')
            ->orderBy('sort_order')
            ->get();

        if ($meetingPacks->count() < 3) {
            $this->command?->warn(
                'PaymentSeeder: published MeetingPack が3件未満です。'
            );

            return;
        }

        $succeededPack = $meetingPacks[0];
        $pendingPack = $meetingPacks[1];
        $failedPack = $meetingPacks[2];

        /*
         * デモ受講生：完了
         */
        $succeededPayment = Payment::create([
            'meeting_pack_id' => $succeededPack->id,
            'user_id' => $demoStudent->id,
            'amount' => $succeededPack->price,
            'quantity' => $succeededPack->meeting_count,
            'status' => PaymentStatus::Succeeded,
            'paid_at' => now(),
            'stripe_session_id' => null,
        ]);

        /*
         * 完了した決済だけ面談回数に反映する。
         */
        MeetingQuotaTransaction::create([
            'user_id' => $demoStudent->id,
            'type' => 'purchased',
            'amount' => $succeededPack->meeting_count,
            'occurred_at' => now(),
            'related_payment_id' => $succeededPayment->id,
        ]);

        /*
         * デモ受講生：保留
         */
        Payment::create([
            'meeting_pack_id' => $pendingPack->id,
            'user_id' => $demoStudent->id,
            'amount' => $pendingPack->price,
            'quantity' => $pendingPack->meeting_count,
            'status' => PaymentStatus::Pending,
            'paid_at' => null,
            'stripe_session_id' => null,
        ]);

        /*
         * デモ受講生：失敗
         */
        Payment::create([
            'meeting_pack_id' => $failedPack->id,
            'user_id' => $demoStudent->id,
            'amount' => $failedPack->price,
            'quantity' => $failedPack->meeting_count,
            'status' => PaymentStatus::Failed,
            'paid_at' => null,
            'stripe_session_id' => null,
        ]);

        /*
         * 固定受講生：失敗
         */
        Payment::create([
            'meeting_pack_id' => $failedPack->id,
            'user_id' => $student->id,
            'amount' => $failedPack->price,
            'quantity' => $failedPack->meeting_count,
            'status' => PaymentStatus::Failed,
            'paid_at' => null,
            'stripe_session_id' => null,
        ]);
    }
}
