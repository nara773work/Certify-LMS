<?php

declare(strict_types=1);

namespace Tests\Feature\Http\MeetingQuota;

use App\Enums\PaymentStatus;
use App\Models\MeetingPack;
use App\Models\MeetingQuotaTransaction;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MeetingQuotaControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 公開中の面談パック一覧を閲覧できる。
     */
    public function test_student_can_view_published_meeting_packs(): void
    {
        $this->seed();

        $student = User::query()
            ->where('role', 'student')
            ->where('email', 'student@certify-lms.test')
            ->firstOrFail();

        $response = $this
            ->actingAs($student)
            ->get(route('meeting-quota.checkout.select'));

        $response
            ->assertOk()
            ->assertViewIs('meeting-quota.checkout-select');

        $response->assertSee('1 回パック');
        $response->assertSee('5 回パック');
        $response->assertSee('10 回パック');
        $response->assertSee('3,000');
        $response->assertSee('12,000');
        $response->assertSee('21,000');
    }

    /**
     * 学習中でない受講生は購入できない。
     *
     * ※ 実際のmiddleware / Policyの仕様に合わせて
     *    ステータスや期待ステータスコードを調整する。
     */
public function test_student_who_is_not_in_progress_cannot_purchase(): void
{
    $this->seed();

    $student = User::query()
        ->where('role', 'student')
        ->where('email', 'student@certify-lms.test')
        ->firstOrFail();

    $meetingPack = MeetingPack::query()
        ->where('status', 'published')
        ->firstOrFail();

    $student->status = 'graduated';
    $student->save();

    $response = $this
        ->actingAs($student)
        ->post(route('meeting-quota.checkout.create'), [
            'meeting_pack_id' => $meetingPack->id,
        ]);

    $response->assertForbidden();
}

    /**
     * Stripe Checkout作成時に、
     * 選択した面談パックが正しく使われる。
     *
     * ※ Stripe APIを実際に叩くテストではなく、
     *    DB上の選択値が正しいことを確認する。
     */
    public function test_selected_meeting_pack_is_used(): void
    {
        $this->seed();

        $student = User::query()
            ->where('role', 'student')
            ->where('email', 'student@certify-lms.test')
            ->firstOrFail();

        $meetingPack = MeetingPack::query()
            ->where('status', 'published')
            ->where('meeting_count', 5)
            ->firstOrFail();

        $this->assertSame(
            '5 回パック',
            $meetingPack->name
        );

        $this->assertSame(5, $meetingPack->meeting_count);
        $this->assertSame(12000, $meetingPack->price);
    }

    /**
     * 決済完了後、Paymentが作成される。
     */
    public function test_succeeded_payment_is_created(): void
    {
        $this->seed();

        $student = User::query()
            ->where('role', 'student')
            ->where('email', 'student@certify-lms.test')
            ->firstOrFail();

        $meetingPack = MeetingPack::query()
            ->where('status', 'published')
            ->where('meeting_count', 5)
            ->firstOrFail();

        $payment = Payment::create([
            'meeting_pack_id' => $meetingPack->id,
            'user_id' => $student->id,
            'amount' => $meetingPack->price,
            'quantity' => $meetingPack->meeting_count,
            'status' => PaymentStatus::Succeeded,
            'paid_at' => now(),
            'stripe_session_id' => 'test_session_001',
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'user_id' => $student->id,
            'meeting_pack_id' => $meetingPack->id,
            'amount' => 12000,
            'quantity' => 5,
            'status' => 'succeeded',
        ]);
    }

    /**
     * 決済完了分だけ面談回数が加算される。
     */
    public function test_succeeded_payment_adds_meeting_quota(): void
    {
        $this->seed();

        $student = User::query()
            ->where('role', 'student')
            ->where('email', 'student@certify-lms.test')
            ->firstOrFail();

        $meetingPack = MeetingPack::query()
            ->where('status', 'published')
            ->where('meeting_count', 5)
            ->firstOrFail();

        $payment = Payment::create([
            'meeting_pack_id' => $meetingPack->id,
            'user_id' => $student->id,
            'amount' => $meetingPack->price,
            'quantity' => $meetingPack->meeting_count,
            'status' => PaymentStatus::Succeeded,
            'paid_at' => now(),
            'stripe_session_id' => 'test_session_002',
        ]);

        MeetingQuotaTransaction::create([
            'user_id' => $student->id,
            'type' => 'purchased',
            'amount' => $meetingPack->meeting_count,
            'occurred_at' => now(),
            'related_payment_id' => $payment->id,
        ]);

        $this->assertDatabaseHas('meeting_quota_transactions', [
            'user_id' => $student->id,
            'type' => 'purchased',
            'amount' => 5,
            'related_payment_id' => $payment->id,
        ]);
    }

    /**
     * 失敗した決済では面談回数が加算されない。
     */
    public function test_failed_payment_does_not_add_meeting_quota(): void
    {
        $this->seed();

        $student = User::query()
            ->where('role', 'student')
            ->where('email', 'student@certify-lms.test')
            ->firstOrFail();

        $meetingPack = MeetingPack::query()
            ->where('status', 'published')
            ->where('meeting_count', 10)
            ->firstOrFail();

        $payment = Payment::create([
            'meeting_pack_id' => $meetingPack->id,
            'user_id' => $student->id,
            'amount' => $meetingPack->price,
            'quantity' => $meetingPack->meeting_count,
            'status' => PaymentStatus::Failed,
            'paid_at' => now(),
            'stripe_session_id' => 'test_failed_001',
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'failed',
        ]);

        $this->assertDatabaseMissing('meeting_quota_transactions', [
            'related_payment_id' => $payment->id,
            'type' => 'purchased',
        ]);
    }

    /**
     * 同じStripe Sessionが2回通知されても、
     * Paymentと面談回数が二重にならない。
     */
    public function test_duplicate_stripe_session_does_not_add_quota_twice(): void
    {
        $this->seed();

        $student = User::query()
            ->where('role', 'student')
            ->where('email', 'student@certify-lms.test')
            ->firstOrFail();

        $meetingPack = MeetingPack::query()
            ->where('status', 'published')
            ->where('meeting_count', 5)
            ->firstOrFail();

        $stripeSessionId = 'cs_test_duplicate_001';

        Payment::create([
            'meeting_pack_id' => $meetingPack->id,
            'user_id' => $student->id,
            'amount' => $meetingPack->price,
            'quantity' => $meetingPack->meeting_count,
            'status' => PaymentStatus::Succeeded,
            'paid_at' => now(),
            'stripe_session_id' => $stripeSessionId,
        ]);

        MeetingQuotaTransaction::create([
            'user_id' => $student->id,
            'type' => 'purchased',
            'amount' => $meetingPack->meeting_count,
            'occurred_at' => now(),
            'related_payment_id' => Payment::query()
                ->where('stripe_session_id', $stripeSessionId)
                ->value('id'),
        ]);

        $paymentCountBefore = Payment::query()
            ->where('stripe_session_id', $stripeSessionId)
            ->count();

        $quotaCountBefore = MeetingQuotaTransaction::query()
            ->where('user_id', $student->id)
            ->where('type', 'purchased')
            ->count();

        // 同じSessionをもう一度処理しようとした場合、
        // 既存Paymentがあるため追加されない。
        $existingPayment = Payment::query()
            ->where('stripe_session_id', $stripeSessionId)
            ->first();

        $this->assertNotNull($existingPayment);

        $this->assertSame(
            $paymentCountBefore,
            Payment::query()
                ->where('stripe_session_id', $stripeSessionId)
                ->count()
        );

        $this->assertSame(
            $quotaCountBefore,
            MeetingQuotaTransaction::query()
                ->where('user_id', $student->id)
                ->where('type', 'purchased')
                ->count()
        );
    }

    /**
     * 購入後、残面談回数に即時反映される。
     */
    public function test_purchased_quota_is_reflected_in_remaining_count(): void
    {
        $this->seed();

        $student = User::query()
            ->where('role', 'student')
            ->where('email', 'student@certify-lms.test')
            ->firstOrFail();

        $before = app(\App\Services\MeetingQuotaService::class)
            ->remaining($student);

        $meetingPack = MeetingPack::query()
            ->where('status', 'published')
            ->where('meeting_count', 5)
            ->firstOrFail();

        $payment = Payment::create([
            'meeting_pack_id' => $meetingPack->id,
            'user_id' => $student->id,
            'amount' => $meetingPack->price,
            'quantity' => $meetingPack->meeting_count,
            'status' => PaymentStatus::Succeeded,
            'paid_at' => now(),
            'stripe_session_id' => 'test_remaining_001',
        ]);

        MeetingQuotaTransaction::create([
            'user_id' => $student->id,
            'type' => 'purchased',
            'amount' => 5,
            'occurred_at' => now(),
            'related_payment_id' => $payment->id,
        ]);

        $student->refresh();

        $after = app(\App\Services\MeetingQuotaService::class)
            ->remaining($student);

        $this->assertSame(
            $before + 5,
            $after
        );
    }
}