<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'role_id',
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AuditAssignment::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(AuditFormResponse::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(AuditReview::class, 'reviewed_by_user_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(AuditApproval::class, 'approved_by_user_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'uploaded_by_user_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
