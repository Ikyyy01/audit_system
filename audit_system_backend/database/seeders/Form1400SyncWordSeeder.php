<?php

namespace Database\Seeders;

use App\Models\AuditForm;
use App\Models\AuditFormField;
use App\Models\AuditFormSection;
use Illuminate\Database\Seeder;

class Form1400SyncWordSeeder extends Seeder
{
    /**
     * Rebuild Form 1400 (Laporan Risiko) agar strukturnya PERSIS seperti
     * dokumen Word asli "1400 Laporan Risiko PT IAS 2024.docx":
     *
     * Tabel 9 kolom:
     *   No. | Deskripsi Risiko | Kesalahan yang mungkin terjadi | Dampak (H/L) |
     *   Probabilitas (H/L) | Risiko Signifikan? (Y/N) |
     *   Penetapan Asersi, Pervasif atau Seluruh Entitas |
     *   Ringkasan Pendekatan Audit | Ref
     *
     * 2 section:
     *   - Tingkat Laporan Keuangan (8 baris)
     *   - Tingkat Akun (5 baris)
     *
     * Masing-masing section dijadikan 1 field repeater.
     */
    public function run(): void
    {
        $form = AuditForm::where('code', '1400')->first();
        if (!$form) {
            $this->command?->warn('Form 1400 tidak ditemukan.');
            return;
        }

        // Hapus semua section & field lama
        foreach ($form->sections as $sec) {
            $sec->fields()->delete();
            $sec->delete();
        }

        // Kolom repeater (verbatim dari header tabel Word asli)
        $riskCols = [
            ['key' => 'deskripsi_risiko',    'label' => 'Deskripsi Risiko',                                       'type' => 'text',     'width' => '20%'],
            ['key' => 'kesalahan',           'label' => 'Kesalahan yang mungkin terjadi',                         'type' => 'text',     'width' => '20%'],
            ['key' => 'dampak',              'label' => 'Dampak (H/L)',                                           'type' => 'text',     'width' => '6%'],
            ['key' => 'probabilitas',        'label' => 'Probabilitas (H/L)',                                     'type' => 'text',     'width' => '6%'],
            ['key' => 'risiko_signifikan',   'label' => 'Risiko Signifikan? (Y/N)',                               'type' => 'text',     'width' => '7%'],
            ['key' => 'asersi',              'label' => 'Penetapan Asersi, Pervasif atau Seluruh Entitas',        'type' => 'text',     'width' => '15%'],
            ['key' => 'ringkasan_pendekatan','label' => 'Ringkasan Pendekatan Audit',                             'type' => 'text',     'width' => '20%'],
            ['key' => 'ref',                 'label' => 'Ref',                                                    'type' => 'text',     'width' => '6%'],
        ];

        // Section 1: Tingkat Laporan Keuangan
        $sec1 = AuditFormSection::create([
            'form_id'       => $form->id,
            'section_name'  => 'Tingkat Laporan Keuangan (Financial Statement Level)',
            'section_order' => 1,
        ]);

        AuditFormField::create([
            'section_id'   => $sec1->id,
            'field_name'   => 'fs_risks',
            'field_label'  => 'Risiko Tingkat Laporan Keuangan',
            'field_type'   => 'repeater',
            'is_required'  => true,
            'field_order'  => 1,
            'options_json' => $riskCols,
        ]);

        // Section 2: Tingkat Akun
        $sec2 = AuditFormSection::create([
            'form_id'       => $form->id,
            'section_name'  => 'Tingkat Akun (Account Level Risks)',
            'section_order' => 2,
        ]);

        AuditFormField::create([
            'section_id'   => $sec2->id,
            'field_name'   => 'acct_risks',
            'field_label'  => 'Risiko Tingkat Akun',
            'field_type'   => 'repeater',
            'is_required'  => true,
            'field_order'  => 1,
            'options_json' => $riskCols,
        ]);

        $this->command?->info('Form 1400: diubah ke 2 repeater tabel 8-kolom (FS Level + Account Level) sesuai Word asli.');
    }
}
