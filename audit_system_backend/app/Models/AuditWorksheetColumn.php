<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditWorksheetColumn extends Model
{
    protected $fillable = [
        'form_id',
        'column_key',
        'column_label',
        'data_type',
        'column_order',
        'is_formula',
        'formula_expression',
    ];

    protected function casts(): array
    {
        return [
            'is_formula' => 'boolean',
            'column_order' => 'integer',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(AuditForm::class, 'form_id');
    }
}
