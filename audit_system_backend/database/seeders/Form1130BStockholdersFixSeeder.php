<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Perbaikan Form 1130B item 6 (Stockholders) supaya PERSIS mengikuti Excel
 * sumber: "1130 B. Cek Latar Belakang First Pass Data IAS 2024 Rev 1.xlsx"
 * sheet "Kertas Kerja".
 *
 * Excel-nya punya 2 tabel pemegang saham terpisah dengan rumus beda:
 * - PT Indo American Seafoods Tbk : Nilai (Rp) = 50 x Jumlah Lembar
 * - PT Indokom Samudra Persada    : Nilai (Rp) = 1.000.000 x Jumlah Lembar
 * - % Kepemilikan = Jumlah Lembar / SUM(Jumlah Lembar semua baris)
 *
 * Field lama `shareholders_info` (1 tabel gabungan, tanpa rumus) diganti
 * jadi 2 field repeater baru dengan kolom formula. Field lain di 1130B
 * (directors_commissioners, subsidiaries, investments) tidak disentuh
 * karena sudah sesuai dokumen sumber masing-masing (tanpa rumus).
 *
 * Jalankan: php artisan db:seed --class=Form1130BStockholdersFixSeeder
 */
class Form1130BStockholdersFixSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $formId = DB::table('audit_forms')->where('code', '1130B')->value('id');
        if (! $formId) {
            $this->command?->error('Form 1130B tidak ditemukan di audit_forms.');

            return;
        }

        $sectionId = DB::table('audit_form_sections')
            ->where('form_id', $formId)
            ->where('section_name', 'like', '%Pemegang Saham%')
            ->value('id');

        if (! $sectionId) {
            $this->command?->error('Section "Pemegang Saham" di Form 1130B tidak ditemukan.');

            return;
        }

        // Ambil field lama buat tahu urutannya, terus hapus shareholders_info
        $oldField = DB::table('audit_form_fields')
            ->where('section_id', $sectionId)
            ->where('field_name', 'shareholders_info')
            ->first();

        $baseOrder = $oldField->field_order ?? 1;

        DB::table('audit_form_fields')
            ->where('section_id', $sectionId)
            ->where('field_name', 'shareholders_info')
            ->delete();

        $stockholdersParentColumns = json_encode([
            ['key' => 'nama', 'label' => 'Name', 'type' => 'text', 'width' => '35%'],
            ['key' => 'jumlah_lembar', 'label' => 'Number of shares', 'type' => 'number', 'width' => '20%'],
            ['key' => 'nilai_rp', 'label' => 'In IDR', 'type' => 'number', 'width' => '25%', 'total' => true],
            ['key' => 'persentase', 'label' => '% of ownership', 'width' => '15%', 'percent_of' => 'jumlah_lembar'],
        ]);

        $stockholdersSubsidiaryColumns = json_encode([
            ['key' => 'nama', 'label' => 'Name', 'type' => 'text', 'width' => '35%'],
            ['key' => 'jumlah_saham', 'label' => 'Number of shares', 'type' => 'number', 'width' => '20%'],
            ['key' => 'nilai_rp', 'label' => 'In IDR', 'type' => 'number', 'width' => '25%', 'total' => true],
            ['key' => 'persentase', 'label' => '% of ownership', 'width' => '15%', 'percent_of' => 'jumlah_saham'],
        ]);

        DB::table('audit_form_fields')->insert([
            [
                'section_id' => $sectionId,
                'field_name' => 'stockholders_parent',
                'field_label' => 'Susunan Pemegang Saham — PT Indo American Seafoods Tbk',
                'field_type' => 'repeater',
                'is_required' => true,
                'field_order' => $baseOrder,
                'options_json' => $stockholdersParentColumns,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_id' => $sectionId,
                'field_name' => 'stockholders_subsidiary',
                'field_label' => 'Susunan Pemegang Saham — PT Indokom Samudra Persada (Entitas Anak)',
                'field_type' => 'repeater',
                'is_required' => false,
                'field_order' => $baseOrder + 1,
                'options_json' => $stockholdersSubsidiaryColumns,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // Geser urutan field-field sesudahnya (shareholders_background dst) +1
        DB::table('audit_form_fields')
            ->where('section_id', $sectionId)
            ->where('field_order', '>', $baseOrder)
            ->where('field_name', '!=', 'stockholders_subsidiary')
            ->increment('field_order');

        // Isi data jawaban dari angka mentah di Excel (Nama + Jumlah Lembar saja —
        // Nilai Rp & % dihitung otomatis oleh rumus di frontend, tidak perlu disimpan)
        $client = DB::table('clients')->where('name', 'like', '%Indo American%')->first();
        $engagement = $client ? DB::table('engagements')->where('client_id', $client->id)->first() : null;

        if ($engagement) {
            $response = DB::table('audit_form_responses')
                ->where('form_id', $formId)
                ->where('engagement_id', $engagement->id)
                ->first();

            if ($response) {
                $parentFieldId = DB::table('audit_form_fields')->where('section_id', $sectionId)->where('field_name', 'stockholders_parent')->value('id');
                $subFieldId = DB::table('audit_form_fields')->where('section_id', $sectionId)->where('field_name', 'stockholders_subsidiary')->value('id');

                DB::table('audit_form_answers')->updateOrInsert(
                    ['response_id' => $response->id, 'field_id' => $parentFieldId],
                    ['response_value' => json_encode([
                        ['nama' => 'PT Indo American Food', 'jumlah_lembar' => 962500000],
                        ['nama' => 'Saimi Saleh', 'jumlah_lembar' => 82500000],
                        ['nama' => 'Ibnu Syena Alfitra', 'jumlah_lembar' => 55000000],
                        ['nama' => 'Masyarakat (masing-masing dibawah 5%)', 'jumlah_lembar' => 290000000],
                    ]), 'created_at' => $now, 'updated_at' => $now]
                );

                DB::table('audit_form_answers')->updateOrInsert(
                    ['response_id' => $response->id, 'field_id' => $subFieldId],
                    ['response_value' => json_encode([
                        ['nama' => 'Perseroan', 'jumlah_saham' => 1050],
                        ['nama' => 'Saimi Saleh', 'jumlah_saham' => 600],
                        ['nama' => 'Ibnu Syena Alfitra', 'jumlah_saham' => 400],
                    ]), 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        $this->command?->info('Form 1130B: item 6 (Stockholders) berhasil dipecah jadi 2 tabel dengan rumus Rp & % otomatis.');
    }
}
