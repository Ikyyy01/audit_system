<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditReview extends Model
{
    protected $fillable = [
        'response_id',
        'reviewed_by_user_id',
        'review_status',
        'comments',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(AuditFormResponse::class, 'response_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
