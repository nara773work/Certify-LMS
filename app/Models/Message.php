<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AiChatMessageRole;
use App\Enums\AiChatMessageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_chat_conversation_id',
        'content',
        'role',
        'status',
    ];

    protected $casts = [
        'role' => AiChatMessageRole::class,
        'status' => AiChatMessageStatus::class,
    ];

    public function conversation()
    {
        return $this->belongsTo(AiChatConversation::class);
    }
}
