<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiChatConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'enrollment_id',
        'last_message_at',
        'section_id',
        'title',

    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}
