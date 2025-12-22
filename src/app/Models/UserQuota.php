<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserQuota extends Model
{
    use HasFactory;

    protected $table = 'user_quota';

    protected $fillable = [
        'user_id',
        'total_storage_max',
    ];

    protected $casts = [
        'total_storage_max' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
