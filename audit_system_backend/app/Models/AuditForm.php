<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditForm extends Model
{
    protected $fillable = [
        'code',
        'name',
        'parent_form_id',
        'form_type',
        'render_type',
    ];

    public function parentForm(): BelongsTo
    {
        return $this->belongsTo(AuditForm::class, 'parent_form_id');
    }

    public function childForms(): HasMany
    {
        return $this->hasMany(AuditForm::class, 'parent_form_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(AuditFormSection::class, 'form_id');
    }

    public function worksheetColumns(): HasMany
    {
        return $this->hasMany(AuditWorksheetColumn::class, 'form_id')->orderBy('column_order');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(AuditFormResponse::class, 'form_id');
    }
}
