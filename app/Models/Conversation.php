<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'enrollment_id',
        'last_message_at',
        'section_id',
    ];

    function user()
    {
        return $this->belongsTo(User::class);
    }

    function messages()
    {
        return $this->hasMany(Message::class);
    }

    function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    function section()
    {
        return $this->belongsTo(Section::class);
    }
}
