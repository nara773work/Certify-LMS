<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QaReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'qa_thread_id',
        'body',
        'user_id',
    ];

    public function thread()
    {
        return $this->belongsTo(QaThread::class, 'qa_thread_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
