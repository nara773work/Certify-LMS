<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'notifiable',
        'data',
        'read_at',
        'status',
    ];

        public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}
