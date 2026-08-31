<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Engagement extends Model
{
    protected $fillable = [
        'client_id',
        'engagement_code',
        'engagement_year',
        'status',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AuditAssignment::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(AuditFormResponse::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
