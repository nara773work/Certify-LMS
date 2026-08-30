<?php

declare(strict_types=1);

namespace App\UseCases\Meeting;

use App\Models\Meeting;
use App\Models\User;
use App\Actions\Meeting\RefundQuotaAction;
use Illuminate\Support\Facades\DB;

class CancelAction
{
    public function __construct(
        private RefundQuotaAction $refundAction,
    ) {
    }

    public function __invoke(
        Meeting $meeting,
        User $actor,
    ): void {
        DB::transaction(function () use ($meeting, $actor) {

            // キャンセル処理
            $meeting->update([
                'status' => 'canceled',
                'canceled_by_user_id' => $actor->id,
                'canceled_at' => now(),
            ]);

            // 面談回数を返却
            ($this->refundAction)($meeting);
        });

        // Google Calendar から削除
        if ($meeting->google_calendar_event_id) {
            $this->deleteEvent(
                (string) $meeting->coach_id,
                $meeting->google_calendar_event_id,
            );
        }
    }
}