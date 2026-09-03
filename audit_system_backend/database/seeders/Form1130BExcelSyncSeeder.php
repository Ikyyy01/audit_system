<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Form 1130B — Sinkronisasi PERSIS dengan Excel asli:
 * "1130 B. Cek Latar Belakang First Pass Data IAS 2024 Rev 1.xlsx"
 * Sheet "Kertas Kerja"
 *
 * Perbaikan:
 * 1. directors_commissioners: tambah kolom "Date of Birth", label & urutan persis Excel
 * 2. subsidiaries: hapus kolom "% Kepemilikan" (tidak ada di Excel), label persis Excel
 * 3. investments: label "Jumlah Aset sebelum eliminasi", data tahun_pendirian = 2020 (bukan 2000)
 * 4. shareholders_background dipecah jadi 2 field narasi (satu per entitas)
 * 5. Semua label mengikuti bahasa Excel asli (English)
 */
class Form1130BExcelSyncSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $formId = DB::table('audit_forms')->where('code', '1130B')->value('id');
        if (! $formId) {
            $this->command?->error('Form 1130B tidak ditemukan.');
            return;
        }

        // ===== 1. FIX directors_commissioners =====
        // Excel: No | Name | Gender | No Identity | Address | Date of Birth | Nationality | Function
        $directorsColumns = json_encode([
            ['key' => 'nama', 'label' => 'Name', 'type' => 'text', 'width' => '15%'],
            ['key' => 'gender', 'label' => 'Gender', 'type' => 'text', 'width' => '8%'],
            ['key' => 'no_identity', 'label' => 'No Identity', 'type' => 'text', 'width' => '15%'],
            ['key' => 'address', 'label' => 'Address', 'type' => 'text', 'width' => '20%'],
            ['key' => 'date_of_birth', 'label' => 'Date of Birth', 'type' => 'date', 'width' => '10%'],
            ['key' => 'nationality', 'label' => 'Nationality', 'type' => 'text', 'width' => '10%'],
            ['key' => 'function', 'label' => 'Function', 'type' => 'text', 'width' => '12%'],
        ]);

        DB::table('audit_form_fields')
            ->where('field_name', 'directors_commissioners')
            ->update([
                'field_label'  => 'Commissioners and Directors',
                'field_type'   => 'repeater',
                'options_json' => $directorsColumns,
                'updated_at'   => $now,
            ]);

        // ===== 2. FIX subsidiaries =====
        // Excel: No | Entitas Anak | Domisili (NO % Kepemilikan!)
        $subsidiariesColumns = json_encode([
            ['key' => 'nama_entitas', 'label' => 'Entitas Anak', 'type' => 'text', 'width' => '50%'],
            ['key' => 'domisili', 'label' => 'Domisili', 'type' => 'text', 'width' => '40%'],
        ]);

        DB::table('audit_form_fields')
            ->where('field_name', 'subsidiaries')
            ->update([
                'field_label'  => 'Subsidiary',
                'field_type'   => 'repeater',
                'options_json' => $subsidiariesColumns,
                'updated_at'   => $now,
            ]);

        // ===== 3. FIX investments =====
        // Excel: No | Entitas Anak | Kegiatan Usaha | Tahun Pendirian | Tahun Penyertaan | Domisili | Jumlah Aset sebelum eliminasi | Persentasi Kepemilikan
        $investmentsColumns = json_encode([
            ['key' => 'nama_entitas', 'label' => 'Entitas Anak', 'type' => 'text', 'width' => '15%'],
            ['key' => 'kegiatan_usaha', 'label' => 'Kegiatan Usaha', 'type' => 'text', 'width' => '25%'],
            ['key' => 'tahun_pendirian', 'label' => 'Tahun Pendirian', 'type' => 'text', 'width' => '8%'],
            ['key' => 'tahun_penyertaan', 'label' => 'Tahun Penyertaan', 'type' => 'text', 'width' => '8%'],
            ['key' => 'domisili', 'label' => 'Domisili', 'type' => 'text', 'width' => '10%'],
            ['key' => 'jumlah_aset', 'label' => 'Jumlah Aset sebelum eliminasi', 'type' => 'text', 'width' => '18%'],
            ['key' => 'persentase', 'label' => 'Persentasi Kepemilikan', 'type' => 'text', 'width' => '10%'],
        ]);

        DB::table('audit_form_fields')
            ->where('field_name', 'investments')
            ->update([
                'field_label'  => 'Investment',
                'field_type'   => 'repeater',
                'options_json' => $investmentsColumns,
                'updated_at'   => $now,
            ]);

        // ===== 4. SPLIT shareholders_background jadi 2 field narasi =====
        // Hapus field lama shareholders_background, ganti 2 field baru
        $sectionId = DB::table('audit_form_sections')
            ->where('form_id', $formId)
            ->where('section_name', 'like', '%Pemegang Saham%')
            ->value('id');

        if ($sectionId) {
            // Hapus shareholders_background lama
            DB::table('audit_form_fields')
                ->where('section_id', $sectionId)
                ->where('field_name', 'shareholders_background')
                ->delete();

            // Insert 2 field narasi baru
            $parentFieldOrder = DB::table('audit_form_fields')
                ->where('section_id', $sectionId)
                ->where('field_name', 'stockholders_parent')
                ->value('field_order');

            $subFieldOrder = DB::table('audit_form_fields')
                ->where('section_id', $sectionId)
                ->where('field_name', 'stockholders_subsidiary')
                ->value('field_order');

            DB::table('audit_form_fields')->insert([
                [
                    'section_id'   => $sectionId,
                    'field_name'   => 'stockholders_parent_background',
                    'field_label'  => 'PT Indo American Seafoods ("Perusahaan") — Riwayat Pendirian & Perubahan Anggaran Dasar',
                    'field_type'   => 'textarea',
                    'is_required'  => false,
                    'field_order'  => $parentFieldOrder,
                    'options_json' => null,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
                [
                    'section_id'   => $sectionId,
                    'field_name'   => 'stockholders_subsidiary_background',
                    'field_label'  => 'PT Indokom Samudra Persada ("ISP") — Riwayat Pendirian & Perubahan Anggaran Dasar',
                    'field_type'   => 'textarea',
                    'is_required'  => false,
                    'field_order'  => $subFieldOrder,
                    'options_json' => null,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
            ]);

            // Geser stockholders_parent dan stockholders_subsidiary ke belakang narasi
            DB::table('audit_form_fields')
                ->where('section_id', $sectionId)
                ->where('field_name', 'stockholders_parent')
                ->update(['field_order' => $parentFieldOrder + 1]);

            DB::table('audit_form_fields')
                ->where('section_id', $sectionId)
                ->where('field_name', 'stockholders_subsidiary')
                ->update(['field_order' => $subFieldOrder + 1]);
        }

        // ===== 5. UPDATE DATA JAWABAN =====
        $client = DB::table('clients')->where('name', 'like', '%Indo American%')->first();
        $engagement = $client ? DB::table('engagements')->where('client_id', $client->id)->first() : null;

        if ($engagement) {
            $response = DB::table('audit_form_responses')
                ->where('form_id', $formId)
                ->where('engagement_id', $engagement->id)
                ->first();

            if ($response) {
                // directors_commissioners — dengan Date of Birth (Excel serial dates converted)
                $dcFieldId = DB::table('audit_form_fields')->where('field_name', 'directors_commissioners')->value('id');
                if ($dcFieldId) {
                    DB::table('audit_form_answers')->updateOrInsert(
                        ['response_id' => $response->id, 'field_id' => $dcFieldId],
                        ['response_value' => json_encode([
                            ['nama' => 'Ibnu Syena Alfitra', 'gender' => 'Man', 'no_identity' => '3578260805890003', 'address' => 'Laguna Barat 7 Blok A-7/3-5, Kota Surabaya', 'date_of_birth' => '1989-05-08', 'nationality' => 'Indonesia', 'function' => 'President Director'],
                            ['nama' => 'Ibnu Surya Ramadhan', 'gender' => 'Man', 'no_identity' => '3578262502930001', 'address' => 'Laguna Barat 7 Blok A-7/3-5, Kota Surabaya', 'date_of_birth' => '1993-02-25', 'nationality' => 'Indonesia', 'function' => 'Director'],
                            ['nama' => 'Abu Yazid', 'gender' => 'Man', 'no_identity' => '1871011612730002', 'address' => 'Jalan Galunggung Raya F.19, Kota Bandar Lampung', 'date_of_birth' => '1973-12-16', 'nationality' => 'Indonesia', 'function' => 'Director'],
                            ['nama' => 'Saimi Saleh', 'gender' => 'Man', 'no_identity' => '3578260612580001', 'address' => 'Laguna Barat 7 Blok A-7/3-5, Kota Surabaya', 'date_of_birth' => '1958-12-06', 'nationality' => 'Indonesia', 'function' => 'President Commissioner'],
                            ['nama' => 'Leo Herlambang', 'gender' => 'Man', 'no_identity' => '3578012802690001', 'address' => 'Florence J-9 No 9 Pakuwon City, Kota Surabaya', 'date_of_birth' => '1969-02-28', 'nationality' => 'Indonesia', 'function' => 'Independent Commissioner'],
                        ]), 'created_at' => $now, 'updated_at' => $now]
                    );
                }

                // subsidiaries — tanpa % Kepemilikan (sesuai Excel)
                $subFieldId = DB::table('audit_form_fields')->where('field_name', 'subsidiaries')->value('id');
                if ($subFieldId) {
                    DB::table('audit_form_answers')->updateOrInsert(
                        ['response_id' => $response->id, 'field_id' => $subFieldId],
                        ['response_value' => json_encode([
                            ['nama_entitas' => 'PT Indokom Samudera Persada', 'domisili' => 'Bandar Lampung'],
                        ]), 'created_at' => $now, 'updated_at' => $now]
                    );
                }

                // investments — tahun_pendirian = 2020 (bukan 2000), label diperbaiki
                $invFieldId = DB::table('audit_form_fields')->where('field_name', 'investments')->value('id');
                if ($invFieldId) {
                    DB::table('audit_form_answers')->updateOrInsert(
                        ['response_id' => $response->id, 'field_id' => $invFieldId],
                        ['response_value' => json_encode([
                            ['nama_entitas' => 'PT Indokom Samudera Persada', 'kegiatan_usaha' => 'Pembesaran Crustacea Air Payau, Industri Pembekuan Biota Air Lainnya, Industri Makanan dan Masakan Olahan. Perdagangan Besar Hasil Perikanan.', 'tahun_pendirian' => '2020', 'tahun_penyertaan' => '2023', 'domisili' => 'Bandar Lampung', 'jumlah_aset' => '248490881601', 'persentase' => '51.22%'],
                        ]), 'created_at' => $now, 'updated_at' => $now]
                    );
                }

                // stockholders_parent_background (narasi PT IAS dari Excel Row 22)
                $bgParentFieldId = DB::table('audit_form_fields')->where('field_name', 'stockholders_parent_background')->value('id');
                if ($bgParentFieldId) {
                    DB::table('audit_form_answers')->updateOrInsert(
                        ['response_id' => $response->id, 'field_id' => $bgParentFieldId],
                        ['response_value' => 'PT Indo American Seafoods ("Perusahaan") didirikan berdasarkan Akta Notaris No. 5 tanggal 6 April 2006 dari Akhmadi Dachlan, S.H., Notaris di Bandar Lampung. Akta pendirian ini disahkan oleh Menteri Hukum dan Hak Asasi Manusia Republik Indonesia dalam Surat Keputusannya No. C-16465HT.01.01.TH.2006 tanggal 6 Juni 2006. Anggaran Dasar Perusahaan telah mengalami beberapa kali perubahan, terakhir sebagaimana dinyatakan dalam Akta Notaris No. 11 tanggal 20 Mei 2022 dari Akhmadi Dachlan, S.H., M.Kn., mengenai perubahan pasal 3 Anggaran Dasar maksud dan tujuan kegiatan usaha Perusahaan. Akta perubahan ini telah disahkan oleh Menteri Hukum dan Hak Asasi Manusia Republik Indonesia berdasarkan Surat Keputusan No. AHU-0095588.AH.01.11.Tahun 2022 tanggal 24 Mei 2022.', 'created_at' => $now, 'updated_at' => $now]
                    );
                }

                // stockholders_subsidiary_background (narasi ISP dari Excel Rows 35-38)
                $bgSubFieldId = DB::table('audit_form_fields')->where('field_name', 'stockholders_subsidiary_background')->value('id');
                if ($bgSubFieldId) {
                    DB::table('audit_form_answers')->updateOrInsert(
                        ['response_id' => $response->id, 'field_id' => $bgSubFieldId],
                        ['response_value' => 'PT Indokom Samudra Persada ("ISP") adalah suatu perseroan yang didirikan berdasarkan hukum Republik Indonesia, yang didirikan dengan nama PT Indokom Samudera Persada sesuai dengan Akta Pendirian ISP No. 9, tanggal 16 Agustus 2000, dibuat di hadapan Imran Ma\'aruf S.H., Notaris di Kota Bandar Lampung, dan telah mendapatkan pengesahan Menkumham berdasarkan Surat Keputusan No. C-1490 HT.01.01.TH 2001 Tahun 2001 tertanggal 28 Februari 2001, yang telah diumumkan dalam TBNRI No. 5902 pada BNRI No. 74, tanggal 14 September 2001 serta telah didaftarkan dalam Daftar Perseroan No. 303/BH.07.01/VII/2001 tertanggal 17 Juli 2001. Berdasarkan Akta No. 149/2023, struktur permodalan dan susunan pemegang saham ISP adalah sebagai berikut.', 'created_at' => $now, 'updated_at' => $now]
                    );
                }

                // Hapus answer lama shareholders_background
                $oldBgFieldId = DB::table('audit_form_fields')->where('field_name', 'shareholders_background')->value('id');
                if ($oldBgFieldId) {
                    DB::table('audit_form_answers')->where('response_id', $response->id)->where('field_id', $oldBgFieldId)->delete();
                }
            }
        }

        $this->command?->info('Form 1130B: Sinkronisasi dengan Excel selesai! (Date of Birth, subsidiaries tanpa %, investments tahun 2020, narasi terpisah)');
    }
}
