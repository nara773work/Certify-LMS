<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MeetingQuotaTransactionType;
use App\Enums\PaymentStatus;
use App\Enums\UserStatus;
use App\Models\MeetingPack;
use App\Models\MeetingQuotaTransaction;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MeetingQuotaController extends Controller
{
    /**
     * 面談パック購入画面
     */
    public function index()
    {
        $plans = MeetingPack::query()
            ->where('status', 'published')
            ->orderBy('sort_order')
            ->get();

        return view(
            'meeting-quota.checkout-select',
            compact('plans')
        );
    }

    /**
     * Stripe Checkoutへ遷移する。
     */
    public function store(Request $request)
{
    $user = $request->user();

    /*
     * 学習中の受講生のみ購入できる。
     */
    if (
        $user === null
        || $user->role !== 'student'
        || $user->status !== 'in_progress'
    ) {
        abort(403);
    }

    $meetingPack = MeetingPack::query()
        ->where('id', $request->meeting_pack_id)
        ->where('status', 'published')
        ->firstOrFail();

    \Stripe\Stripe::setApiKey(
        config('services.stripe.secret')
    );

        $session = \Stripe\Checkout\Session::create([
            'mode' => 'payment',

            'metadata' => [
                'user_id' => (string) auth()->id(),
                'meeting_pack_id' => (string) $meetingPack->id,
            ],

            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'jpy',
                        'product_data' => [
                            'name' => $meetingPack->name,
                        ],
                        'unit_amount' => (int) $meetingPack->price,
                    ],
                    'quantity' => 1,
                ],
            ],

            'success_url' => route('meeting-quota.success')
                . '?session_id={CHECKOUT_SESSION_ID}',

            'cancel_url' => route(
                'meeting-quota.checkout.select'
            ),
        ]);

        return redirect()->away($session->url);
    }

    /**
     * 決済完了画面
     */
    public function success()
    {
        $payment = Payment::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->first();

        return view(
            'meeting-quota.success',
            compact('payment')
        );
    }

    /**
 * Stripe Webhook
 *
 * checkout.session.completed を受け取って、
 * Stripe署名を検証したうえで、
 * Payment作成 + 面談回数加算を行う。
 */
public function stripe(Request $request): Response
{
    $payload = $request->getContent();

    /*
     * Stripeから送られてきた署名を取得する。
     */
    $signature = $request->header('Stripe-Signature');

    if ($signature === null) {
        Log::error('Stripe webhook signature missing');

        return response('Bad Request', 400);
    }

    /*
     * Stripe Webhook Secretを取得する。
     */
    $webhookSecret = config('services.stripe.webhook_secret');

    if ($webhookSecret === null) {
        Log::error('Stripe webhook secret is not configured');

        return response('Server Error', 500);
    }

    /*
     * Stripeの署名を検証する。
     *
     * 署名が不正な場合は、
     * Stripeから正当に送信された通知ではないため処理しない。
     */
    try {
        $event = \Stripe\Webhook::constructEvent(
            $payload,
            $signature,
            $webhookSecret
        );
    } catch (\UnexpectedValueException $e) {
        Log::error('Stripe webhook invalid payload', [
            'message' => $e->getMessage(),
        ]);

        return response('Bad Request', 400);
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        Log::error('Stripe webhook signature verification failed', [
            'message' => $e->getMessage(),
        ]);

        return response('Bad Request', 400);
    }

    Log::info('Stripe webhook received', [
        'type' => $event->type ?? null,
    ]);

    /*
     * checkout.session.completed 以外は何もしない。
     */
    if (($event->type ?? null) !== 'checkout.session.completed') {
        return response('OK', 200);
    }

    $session = $event->data->object;

    Log::info('Stripe session data', [
        'session_id' => $session->id ?? null,
        'metadata' => $session->metadata ?? null,
    ]);

    $userId = $session->metadata->user_id ?? null;
    $meetingPackId = $session->metadata->meeting_pack_id ?? null;

    if ($userId === null || $meetingPackId === null) {
        Log::error('Stripe webhook metadata missing');

        return response('Bad Request', 400);
    }

    /*
     * 購入した面談パックを取得する。
     */
    $meetingPack = MeetingPack::query()
        ->where('id', $meetingPackId)
        ->where('status', 'published')
        ->first();

    if ($meetingPack === null) {
        Log::error('Stripe webhook meeting pack not found', [
            'meeting_pack_id' => $meetingPackId,
        ]);

        return response('Bad Request', 400);
    }

    /*
     * 同じStripe Checkout Sessionを
     * 二重処理しない。
     */
    $existingPayment = Payment::query()
        ->where('stripe_session_id', $session->id)
        ->first();

    if ($existingPayment !== null) {
        return response('OK', 200);
    }

    /*
     * Payment作成と面談回数加算を
     * 1つのトランザクションとして処理する。
     *
     * どちらかが失敗した場合、
     * Paymentも面談回数も保存されない。
     */
    DB::transaction(function () use (
        $userId,
        $meetingPack,
        $session
    ) {
        $payment = Payment::create([
            'user_id' => $userId,
            'meeting_pack_id' => $meetingPack->id,
            'quantity' => $meetingPack->meeting_count,
            'amount' => $session->amount_total ?? $meetingPack->price,
            'status' => PaymentStatus::Succeeded,
            'paid_at' => now(),
            'stripe_session_id' => $session->id,
        ]);

        /*
         * 購入した面談回数を加算する。
         */
        MeetingQuotaTransaction::create([
            'user_id' => $userId,
            'type' => MeetingQuotaTransactionType::Purchased,
            'amount' => $meetingPack->meeting_count,
            'occurred_at' => now(),
            'related_payment_id' => $payment->id,
        ]);

        Log::info('Meeting quota purchased', [
            'user_id' => $userId,
            'meeting_pack_id' => $meetingPack->id,
            'quantity' => $meetingPack->meeting_count,
            'payment_id' => $payment->id,
        ]);
    });

    return response('OK', 200);
}
}