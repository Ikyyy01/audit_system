<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditFormResponse extends Model
{
    protected $fillable = [
        'form_id',
        'engagement_id',
        'user_id',
        'status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(AuditForm::class, 'form_id');
    }

    public function engagement(): BelongsTo
    {
        return $this->belongsTo(Engagement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AuditFormAnswer::class, 'response_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(AuditReview::class, 'response_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(AuditApproval::class, 'response_id');
    }
}
