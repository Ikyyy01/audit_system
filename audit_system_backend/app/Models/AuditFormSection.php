<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditFormSection extends Model
{
    protected $fillable = [
        'form_id',
        'section_name',
        'section_order',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(AuditForm::class, 'form_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(AuditFormField::class, 'section_id');
    }
}
