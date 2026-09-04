<?php

namespace Database\Seeders;

use App\Models\AuditForm;
use App\Models\AuditFormField;
use App\Models\AuditFormSection;
use Illuminate\Database\Seeder;

/**
 * Form 1130B — Sinkronisasi 100% PERSIS dengan Excel & Template Generik:
 * Tanpa nama klien spesifik pada template pertanyaan/label.
 *
 * Struktur Section "Pemegang Saham (Stockholders)":
 * 1. stockholders_about (textarea): Tentang
 * 2. stockholders_parent_background (textarea): Riwayat Pendirian & Perubahan Anggaran Dasar
 * 3. stockholders_parent (repeater): Susunan Pemegang Saham
 * 4. stockholders_subsidiary_background (textarea): Riwayat Pendirian & Perubahan Anggaran Dasar (Entitas Anak)
 * 5. stockholders_subsidiary (repeater): Susunan Pemegang Saham (Entitas Anak)
 */
class Form1130BExcelSyncSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $form = AuditForm::where('code', '1130B')->first();
        if (!$form) {
            $this->command?->error('Form 1130B tidak ditemukan.');
            return;
        }

        // ===== 1. SECTION PEMEGANG SAHAM (GENERIC TEMPLATE) =====
        $stockholdersSection = AuditFormSection::where('form_id', $form->id)
            ->where('section_name', 'like', '%Pemegang Saham%')
            ->first();

        if ($stockholdersSection) {
            // Bersihkan SEMUA field di section ini terlebih dahulu agar tidak ada duplikasi
            AuditFormField::where('section_id', $stockholdersSection->id)->delete();

            $stockholdersParentColumns = [
                ['key' => 'nama', 'label' => 'Name', 'type' => 'text', 'width' => '35%'],
                ['key' => 'jumlah_lembar', 'label' => 'Number of shares', 'type' => 'number', 'width' => '20%'],
                [
                    'key' => 'nilai_rp', 'label' => 'In IDR', 'type' => 'number', 'width' => '25%', 'total' => true,
                    'formula' => 'jumlah_lembar * __multiplier__',
                    'multiplier' => ['source' => 'jumlah_lembar', 'label' => 'Nilai per Lembar (IDR)', 'default' => 50],
                ],
                ['key' => 'persentase', 'label' => '% of ownership', 'width' => '15%', 'percent_of' => 'jumlah_lembar'],
            ];

            $stockholdersSubsidiaryColumns = [
                ['key' => 'nama', 'label' => 'Name', 'type' => 'text', 'width' => '35%'],
                ['key' => 'jumlah_saham', 'label' => 'Number of shares', 'type' => 'number', 'width' => '20%'],
                [
                    'key' => 'nilai_rp', 'label' => 'In IDR', 'type' => 'number', 'width' => '25%', 'total' => true,
                    'formula' => 'jumlah_saham * __multiplier__',
                    'multiplier' => ['source' => 'jumlah_saham', 'label' => 'Nilai per Lembar (IDR)', 'default' => 1000000],
                ],
                ['key' => 'persentase', 'label' => '% of ownership', 'width' => '15%', 'percent_of' => 'jumlah_saham'],
            ];

            // 1. Tentang
            AuditFormField::create([
                'section_id'   => $stockholdersSection->id,
                'field_name'   => 'stockholders_about',
                'field_label'  => 'Tentang',
                'field_type'   => 'textarea',
                'is_required'  => false,
                'field_order'  => 1,
                'options_json' => null,
            ]);

            // 2. Riwayat Pendirian & Perubahan Anggaran Dasar
            AuditFormField::create([
                'section_id'   => $stockholdersSection->id,
                'field_name'   => 'stockholders_parent_background',
                'field_label'  => 'Riwayat Pendirian & Perubahan Anggaran Dasar',
                'field_type'   => 'textarea',
                'is_required'  => false,
                'field_order'  => 2,
                'options_json' => null,
            ]);

            // 3. Susunan Pemegang Saham (nama klien dihapus dari template)
            AuditFormField::create([
                'section_id'   => $stockholdersSection->id,
                'field_name'   => 'stockholders_parent',
                'field_label'  => 'Susunan Pemegang Saham',
                'field_type'   => 'repeater',
                'is_required'  => true,
                'field_order'  => 3,
                'options_json' => $stockholdersParentColumns,
            ]);

            // 4. Riwayat Pendirian & Perubahan Anggaran Dasar (Entitas Anak)
            AuditFormField::create([
                'section_id'   => $stockholdersSection->id,
                'field_name'   => 'stockholders_subsidiary_background',
                'field_label'  => 'Riwayat Pendirian & Perubahan Anggaran Dasar (Entitas Anak)',
                'field_type'   => 'textarea',
                'is_required'  => false,
                'field_order'  => 4,
                'options_json' => null,
            ]);

            // 5. Susunan Pemegang Saham (Entitas Anak)
            AuditFormField::create([
                'section_id'   => $stockholdersSection->id,
                'field_name'   => 'stockholders_subsidiary',
                'field_label'  => 'Susunan Pemegang Saham (Entitas Anak)',
                'field_type'   => 'repeater',
                'is_required'  => false,
                'field_order'  => 5,
                'options_json' => $stockholdersSubsidiaryColumns,
            ]);
        }

        // ===== 2. DIRECTORS & COMMISSIONERS =====
        $directorsColumns = [
            ['key' => 'nama', 'label' => 'Name', 'type' => 'text', 'width' => '15%'],
            ['key' => 'gender', 'label' => 'Gender', 'type' => 'text', 'width' => '8%'],
            ['key' => 'no_identity', 'label' => 'No Identity', 'type' => 'text', 'width' => '15%'],
            ['key' => 'address', 'label' => 'Address', 'type' => 'text', 'width' => '20%'],
            ['key' => 'date_of_birth', 'label' => 'Date of Birth', 'type' => 'date', 'width' => '10%'],
            ['key' => 'nationality', 'label' => 'Nationality', 'type' => 'text', 'width' => '10%'],
            ['key' => 'function', 'label' => 'Function', 'type' => 'text', 'width' => '12%'],
        ];

        AuditFormField::where('field_name', 'directors_commissioners')->update([
            'field_label'  => 'Commissioners and Directors',
            'field_type'   => 'repeater',
            'options_json' => $directorsColumns,
            'updated_at'   => $now,
        ]);

        // ===== 3. SUBSIDIARIES =====
        $subsidiariesColumns = [
            ['key' => 'nama_entitas', 'label' => 'Entitas Anak', 'type' => 'text', 'width' => '50%'],
            ['key' => 'domisili', 'label' => 'Domisili', 'type' => 'text', 'width' => '40%'],
        ];

        AuditFormField::where('field_name', 'subsidiaries')->update([
            'field_label'  => 'Subsidiary',
            'field_type'   => 'repeater',
            'options_json' => $subsidiariesColumns,
            'updated_at'   => $now,
        ]);

        // ===== 4. INVESTMENTS =====
        $investmentsColumns = [
            ['key' => 'nama_entitas', 'label' => 'Entitas Anak', 'type' => 'text', 'width' => '15%'],
            ['key' => 'kegiatan_usaha', 'label' => 'Kegiatan Usaha', 'type' => 'text', 'width' => '25%'],
            ['key' => 'tahun_pendirian', 'label' => 'Tahun Pendirian', 'type' => 'text', 'width' => '8%'],
            ['key' => 'tahun_penyertaan', 'label' => 'Tahun Penyertaan', 'type' => 'text', 'width' => '8%'],
            ['key' => 'domisili', 'label' => 'Domisili', 'type' => 'text', 'width' => '10%'],
            ['key' => 'jumlah_aset', 'label' => 'Jumlah Aset sebelum eliminasi', 'type' => 'text', 'width' => '18%'],
            ['key' => 'persentase', 'label' => 'Persentasi Kepemilikan', 'type' => 'text', 'width' => '10%'],
        ];

        AuditFormField::where('field_name', 'investments')->update([
            'field_label'  => 'Investment',
            'field_type'   => 'repeater',
            'options_json' => $investmentsColumns,
            'updated_at'   => $now,
        ]);

        $this->command?->info('Form 1130B: Seluruh label pertanyaan template telah bersih dari nama klien.');
    }
}
