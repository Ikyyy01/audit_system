<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditFormField extends Model
{
    protected $fillable = [
        'section_id',
        'field_name',
        'field_label',
        'field_type',
        'is_required',
        'field_order',
        'options_json',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'options_json' => 'array',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(AuditFormSection::class, 'section_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AuditFormAnswer::class, 'field_id');
    }
}
