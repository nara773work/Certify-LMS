<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiChatModels extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'body',
    ];

    function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
