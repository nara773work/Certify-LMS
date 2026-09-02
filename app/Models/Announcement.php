<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AnnouncementTargetType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
