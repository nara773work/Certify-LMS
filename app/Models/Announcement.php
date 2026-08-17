<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\AnnouncementTargetType;

class Announcement extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'body',
        'target_type',
        'dispatched_at',
        'created_by',
    ];

    protected $casts = [
        'target_type' => AnnouncementTargetType::class,
        'dispatched_at' => 'datetime',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function createdBy()
{
    return $this->belongsTo(User::class, 'created_by');
}
}
