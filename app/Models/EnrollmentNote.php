<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class EnrollmentNote extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'body',
        'enrollment_id'
    ];

    public function user()
{
    return $this->belongsTo(User::class);
}

    public function author(): BelongsTo
{
    return $this->belongsTo(User::class, 'user_id');
}

    public function enrollment(): BelongsTo
{
    return $this->belongsTo(Enrollment::class);
}
}
