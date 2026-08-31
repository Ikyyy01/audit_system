<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $fillable = [
        'engagement_id',
        'form_id',
        'document_name',
        'file_path',
        'mime_type',
        'file_size',
        'uploaded_by_user_id',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function engagement(): BelongsTo
    {
        return $this->belongsTo(Engagement::class);
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(AuditForm::class, 'form_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }
}
