<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_pack_id',
        'user_id',
        'plan_id',
        'quantity',
        'amount',
        'status',
        'paid_at',
        'stripe_session_id',
    ];

    protected $casts = [
        'status' => PaymentStatus::class,
        'paid_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(
            Plan::class,
            'plan_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function meetingPack(): BelongsTo
    {
        return $this->belongsTo(
            MeetingPack::class,
            'meeting_pack_id'
        );
    }
}
