<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditFormAnswer extends Model
{
    protected $fillable = [
        'response_id',
        'field_id',
        'response_value',
    ];

    public function response(): BelongsTo
    {
        return $this->belongsTo(AuditFormResponse::class, 'response_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(AuditFormField::class, 'field_id');
    }
}
