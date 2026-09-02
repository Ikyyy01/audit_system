<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditWorksheetRow extends Model
{
    protected $fillable = [
        'response_id',
        'row_order',
        'row_type',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'row_order' => 'integer',
        ];
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(AuditFormResponse::class, 'response_id');
    }
}
