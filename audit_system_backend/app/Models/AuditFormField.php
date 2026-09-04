<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PENTING soal kolom `options_json`: isinya beda bentuk tergantung `field_type`,
 * bukan berarti field ini "nakal", tapi supaya kita gak perlu tabel terpisah
 * buat tiap variasi field. Dua bentuk yang dipakai sekarang:
 *
 * - field_type = 'dropdown'  -> array pilihan: [{value, label}, ...]
 * - field_type = 'repeater'  -> array definisi kolom tabel:
 *     [{key, label, type?, width?, formula?, multiplier?, percent_of?, total?}, ...]
 *   (dipakai RepeaterField.vue di frontend, lihat AuditWorksheetColumn buat
 *   pola serupa di level form penuh / render_type=worksheet)
 *
 * Pakai dropdownOptions()/repeaterColumns() di bawah biar kode pemanggil
 * jelas maksudnya, daripada akses $field->options_json langsung di banyak
 * tempat tanpa konteks field_type-nya apa.
 */
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

    /** Pilihan dropdown [{value, label}, ...] — hanya berlaku field_type='dropdown'. */
    public function dropdownOptions(): array
    {
        return $this->field_type === 'dropdown' ? ($this->options_json ?? []) : [];
    }

    /** Definisi kolom tabel repeater — hanya berlaku field_type='repeater'. */
    public function repeaterColumns(): array
    {
        return $this->field_type === 'repeater' ? ($this->options_json ?? []) : [];
    }
}
