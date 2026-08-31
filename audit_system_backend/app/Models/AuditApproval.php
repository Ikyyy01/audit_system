<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditApproval extends Model
{
    protected $fillable = [
        'response_id',
        'approved_by_user_id',
        'approval_status',
        'comments',
        'approval_date',
    ];

    protected function casts(): array
    {
        return [
            'approval_date' => 'datetime',
        ];
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(AuditFormResponse::class, 'response_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
