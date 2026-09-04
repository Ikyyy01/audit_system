<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Konversi field 1130B (directors_commissioners, subsidiaries, investments)
 * dari `textarea` menjadi `repeater` dengan struktur kolom terdefinisi rapi,
 * serta ubah data jawaban eksisting (format pipe '|') menjadi JSON array.
 */
class Form1130BRepeaterFixSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $formId = DB::table('audit_forms')->where('code', '1130B')->value('id');
        if (! $formId) {
            $this->command?->error('Form 1130B tidak ditemukan.');
            return;
        }

        // 1. Definition Kolom untuk directors_commissioners
        $directorsColumns = json_encode([
            ['key' => 'nama', 'label' => 'Nama Person', 'type' => 'text', 'width' => '20%'],
            ['key' => 'jenis_kelamin', 'label' => 'Jenis Kelamin', 'type' => 'text', 'width' => '10%'],
            ['key' => 'no_identitas', 'label' => 'No. Identitas (NIK)', 'type' => 'text', 'width' => '15%'],
            ['key' => 'domisili', 'label' => 'Domisili / Kota', 'type' => 'text', 'width' => '15%'],
            ['key' => 'kewarganegaraan', 'label' => 'Kewarganegaraan', 'type' => 'text', 'width' => '12%'],
            ['key' => 'jabatan', 'label' => 'Jabatan', 'type' => 'text', 'width' => '18%'],
        ]);

        // 2. Definition Kolom untuk subsidiaries
        $subsidiariesColumns = json_encode([
            ['key' => 'nama_entitas', 'label' => 'Nama Entitas Anak', 'type' => 'text', 'width' => '40%'],
            ['key' => 'domisili', 'label' => 'Domisili / Lokasi', 'type' => 'text', 'width' => '30%'],
            ['key' => 'persentase', 'label' => '% Kepemilikan', 'type' => 'text', 'width' => '20%'],
        ]);

        // 3. Definition Kolom untuk investments
        $investmentsColumns = json_encode([
            ['key' => 'nama_entitas', 'label' => 'Nama Entitas', 'type' => 'text', 'width' => '20%'],
            ['key' => 'kegiatan_usaha', 'label' => 'Kegiatan Usaha', 'type' => 'text', 'width' => '20%'],
            ['key' => 'tahun_pendirian', 'label' => 'Tahun Pendirian', 'type' => 'text', 'width' => '10%'],
            ['key' => 'tahun_penyertaan', 'label' => 'Tahun Penyertaan', 'type' => 'text', 'width' => '10%'],
            ['key' => 'domisili', 'label' => 'Domisili', 'type' => 'text', 'width' => '12%'],
            ['key' => 'jumlah_aset', 'label' => 'Jumlah Aset', 'type' => 'text', 'width' => '15%'],
            ['key' => 'persentase', 'label' => '% Kepemilikan', 'type' => 'text', 'width' => '10%'],
        ]);

        // Update tipe & options_json di audit_form_fields
        DB::table('audit_form_fields')
            ->where('field_name', 'directors_commissioners')
            ->update([
                'field_label'  => 'Daftar Direksi dan Dewan Komisaris',
                'field_type'   => 'repeater',
                'options_json' => $directorsColumns,
                'updated_at'   => $now,
            ]);

        DB::table('audit_form_fields')
            ->where('field_name', 'subsidiaries')
            ->update([
                'field_label'  => 'Daftar Entitas Anak (Subsidiary)',
                'field_type'   => 'repeater',
                'options_json' => $subsidiariesColumns,
                'updated_at'   => $now,
            ]);

        DB::table('audit_form_fields')
            ->where('field_name', 'investments')
            ->update([
                'field_label'  => 'Daftar Investasi & Penyertaan',
                'field_type'   => 'repeater',
                'options_json' => $investmentsColumns,
                'updated_at'   => $now,
            ]);

        // Update data jawaban eksisting agar ke-parse ke JSON per-kolom
        $client = DB::table('clients')->where('name', 'like', '%Indo American%')->first();
        $engagement = $client ? DB::table('engagements')->where('client_id', $client->id)->first() : null;

        if ($engagement) {
            $response = DB::table('audit_form_responses')
                ->where('form_id', $formId)
                ->where('engagement_id', $engagement->id)
                ->first();

            if ($response) {
                // Answers for directors_commissioners
                $dcFieldId = DB::table('audit_form_fields')->where('field_name', 'directors_commissioners')->value('id');
                if ($dcFieldId) {
                    DB::table('audit_form_answers')->updateOrInsert(
                        ['response_id' => $response->id, 'field_id' => $dcFieldId],
                        ['response_value' => json_encode([
                            ['nama' => 'Ibnu Syena Alfitra', 'jenis_kelamin' => 'Laki-laki', 'no_identitas' => '3578260805890003', 'domisili' => 'Surabaya', 'kewarganegaraan' => 'Indonesia', 'jabatan' => 'Direktur Utama'],
                            ['nama' => 'Ibnu Surya Ramadhan', 'jenis_kelamin' => 'Laki-laki', 'no_identitas' => '3578262502930001', 'domisili' => 'Surabaya', 'kewarganegaraan' => 'Indonesia', 'jabatan' => 'Direktur'],
                            ['nama' => 'Abu Yazid', 'jenis_kelamin' => 'Laki-laki', 'no_identitas' => '1871011612730002', 'domisili' => 'Bandar Lampung', 'kewarganegaraan' => 'Indonesia', 'jabatan' => 'Direktur'],
                            ['nama' => 'Saimi Saleh', 'jenis_kelamin' => 'Laki-laki', 'no_identitas' => '3578260612580001', 'domisili' => 'Surabaya', 'kewarganegaraan' => 'Indonesia', 'jabatan' => 'Komisaris Utama'],
                            ['nama' => 'Dr. Leo Herlambang', 'jenis_kelamin' => 'Laki-laki', 'no_identitas' => '3578012802690001', 'domisili' => 'Surabaya', 'kewarganegaraan' => 'Indonesia', 'jabatan' => 'Komisaris Independen'],
                        ]), 'created_at' => $now, 'updated_at' => $now]
                    );
                }

                // Answers for subsidiaries
                $subFieldId = DB::table('audit_form_fields')->where('field_name', 'subsidiaries')->value('id');
                if ($subFieldId) {
                    DB::table('audit_form_answers')->updateOrInsert(
                        ['response_id' => $response->id, 'field_id' => $subFieldId],
                        ['response_value' => json_encode([
                            ['nama_entitas' => 'PT Indokom Samudera Persada', 'domisili' => 'Bandar Lampung', 'persentase' => '51.22%'],
                        ]), 'created_at' => $now, 'updated_at' => $now]
                    );
                }

                // Answers for investments
                $invFieldId = DB::table('audit_form_fields')->where('field_name', 'investments')->value('id');
                if ($invFieldId) {
                    DB::table('audit_form_answers')->updateOrInsert(
                        ['response_id' => $response->id, 'field_id' => $invFieldId],
                        ['response_value' => json_encode([
                            ['nama_entitas' => 'PT Indokom Samudera Persada', 'kegiatan_usaha' => 'Pembesaran Crustacea Air Payau, Cold Storage, Pengolahan Udang', 'tahun_pendirian' => '2000', 'tahun_penyertaan' => '2023', 'domisili' => 'Bandar Lampung', 'jumlah_aset' => 'Rp 248.490.881.601', 'persentase' => '51.22%'],
                        ]), 'created_at' => $now, 'updated_at' => $now]
                    );
                }
            }
        }

        $this->command?->info('Form 1130B: directors_commissioners, subsidiaries, dan investments berhasil diubah ke repeater terstruktur!');
    }
}
