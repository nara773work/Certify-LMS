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
    ];

    protected $casts = [
        'target_type' => AnnouncementTargetType::class,
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
