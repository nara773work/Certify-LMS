<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\MeetingPackStatus;
use App\Enums\PaymentStatus;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_pack_id',
        'user_id',
        'amount',
        'meeting_count',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'status' => PaymentStatus::class,
        'paid_at' => 'datetime',
    ];

    public function meetingPack(): BelongsTo
    {
        return $this->belongsTo(MeetingPack::class,'meeting_pack_id');
    }

    public function user()
{
    return $this->belongsTo(User::class);
}

}
