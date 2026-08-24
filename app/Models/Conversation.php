<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversationModels extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
    ];

    function user()
    {
        return $this->belongsTo(User::class);
    }

    function aiChats()
    {
        return $this->hasMany(AiChat::class);
    }
}
