<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Konversi field-field yang isinya daftar berulang (pemegang saham, direksi,
 * pelanggan utama, entitas anak, investasi, dll.) dari textarea menjadi
 * repeater. Kolom-kolom tiap repeater didefinisikan di options_json.
 *
 * Data answer yang sudah ada (teks multi-baris) tetap di audit_form_answers
 * — frontend akan fallback render sebagai textarea read-only kalau nilainya
 * bukan JSON array, jadi backward-compatible.
 */
return new class extends Migration
{
    public function up(): void
    {
        $repeaterFields = [
            // ── Form 1110 ─────────────────────────────────────────
            'name_of_shareholders' => [
                ['key' => 'nama', 'label' => 'Nama Pemegang Saham', 'type' => 'text', 'width' => '35%'],
                ['key' => 'persentase', 'label' => 'Kepemilikan (%)', 'type' => 'number', 'width' => '15%'],
                ['key' => 'jumlah_lembar', 'label' => 'Jumlah Lembar', 'type' => 'text', 'width' => '25%'],
                ['key' => 'nilai_nominal', 'label' => 'Nilai Nominal (Rp)', 'type' => 'text', 'width' => '25%'],
            ],
            'name_of_management' => [
                ['key' => 'jabatan', 'label' => 'Jabatan', 'type' => 'text', 'width' => '35%'],
                ['key' => 'nama', 'label' => 'Nama', 'type' => 'text', 'width' => '65%'],
            ],
            'total_assets' => [
                ['key' => 'kategori', 'label' => 'Kategori Aset', 'type' => 'text', 'width' => '50%'],
                ['key' => 'nilai', 'label' => 'Jumlah (Rp)', 'type' => 'text', 'width' => '50%'],
            ],
            'main_customers_vendors' => [
                ['key' => 'nama', 'label' => 'Nama', 'type' => 'text', 'width' => '40%'],
                ['key' => 'tipe', 'label' => 'Tipe (Customer/Vendor)', 'type' => 'text', 'width' => '30%'],
                ['key' => 'keterangan', 'label' => 'Keterangan', 'type' => 'text', 'width' => '30%'],
            ],

            // ── Form 1610 (Fase1000SisaFieldsSeeder) ──────────────
            'shareholders_info' => [
                ['key' => 'nama', 'label' => 'Nama Pemegang Saham', 'type' => 'text', 'width' => '30%'],
                ['key' => 'jumlah_saham', 'label' => 'Jumlah Saham', 'type' => 'text', 'width' => '20%'],
                ['key' => 'nilai_idr', 'label' => 'Nilai (IDR)', 'type' => 'text', 'width' => '25%'],
                ['key' => 'persentase', 'label' => 'Kepemilikan (%)', 'type' => 'number', 'width' => '15%'],
            ],
            'directors_commissioners' => [
                ['key' => 'no', 'label' => 'No', 'type' => 'text', 'width' => '5%'],
                ['key' => 'nama', 'label' => 'Nama', 'type' => 'text', 'width' => '20%'],
                ['key' => 'jenis_kelamin', 'label' => 'JK', 'type' => 'text', 'width' => '8%'],
                ['key' => 'no_identitas', 'label' => 'No. Identitas', 'type' => 'text', 'width' => '15%'],
                ['key' => 'alamat', 'label' => 'Alamat', 'type' => 'text', 'width' => '17%'],
                ['key' => 'tgl_lahir', 'label' => 'Tgl Lahir', 'type' => 'date', 'width' => '10%'],
                ['key' => 'kebangsaan', 'label' => 'Kebangsaan', 'type' => 'text', 'width' => '10%'],
                ['key' => 'jabatan', 'label' => 'Jabatan', 'type' => 'text', 'width' => '15%'],
            ],
            'subsidiaries' => [
                ['key' => 'no', 'label' => 'No', 'type' => 'text', 'width' => '10%'],
                ['key' => 'nama_entitas', 'label' => 'Nama Entitas Anak', 'type' => 'text', 'width' => '50%'],
                ['key' => 'domisili', 'label' => 'Domisili', 'type' => 'text', 'width' => '40%'],
            ],
            'investments' => [
                ['key' => 'no', 'label' => 'No', 'type' => 'text', 'width' => '5%'],
                ['key' => 'nama_entitas', 'label' => 'Nama Entitas', 'type' => 'text', 'width' => '18%'],
                ['key' => 'kegiatan_usaha', 'label' => 'Kegiatan Usaha', 'type' => 'text', 'width' => '15%'],
                ['key' => 'thn_pendirian', 'label' => 'Thn Pendirian', 'type' => 'text', 'width' => '10%'],
                ['key' => 'thn_penyertaan', 'label' => 'Thn Penyertaan', 'type' => 'text', 'width' => '10%'],
                ['key' => 'domisili', 'label' => 'Domisili', 'type' => 'text', 'width' => '12%'],
                ['key' => 'jumlah_aset', 'label' => 'Jumlah Aset', 'type' => 'text', 'width' => '15%'],
                ['key' => 'persentase', 'label' => '% Kepemilikan', 'type' => 'number', 'width' => '10%'],
            ],
            'searched_names' => [
                ['key' => 'no', 'label' => 'No', 'type' => 'text', 'width' => '10%'],
                ['key' => 'nama', 'label' => 'Nama yang Diperiksa', 'type' => 'text', 'width' => '45%'],
                ['key' => 'jabatan', 'label' => 'Jabatan / Relasi', 'type' => 'text', 'width' => '45%'],
            ],
        ];

        foreach ($repeaterFields as $fieldName => $columns) {
            DB::table('audit_form_fields')
                ->where('field_name', $fieldName)
                ->update([
                    'field_type'    => 'repeater',
                    'options_json'  => json_encode($columns),
                    'updated_at'    => now(),
                ]);
        }
    }

    public function down(): void
    {
        $fieldNames = [
            'name_of_shareholders',
            'name_of_management',
            'total_assets',
            'main_customers_vendors',
            'shareholders_info',
            'directors_commissioners',
            'subsidiaries',
            'investments',
            'searched_names',
        ];

        DB::table('audit_form_fields')
            ->whereIn('field_name', $fieldNames)
            ->update([
                'field_type'   => 'textarea',
                'options_json' => null,
                'updated_at'   => now(),
            ]);
    }
};
