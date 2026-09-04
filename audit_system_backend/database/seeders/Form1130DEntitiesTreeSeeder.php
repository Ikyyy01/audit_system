<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Perbaikan Form 1130D supaya PERSIS mengikuti dokumen sumber:
 * "1130 D. Entities Tree PT IAS 2024 Rev 1.docx"
 *
 * Dokumen sumber isinya diagram struktur kepemilikan (org chart) dengan panah:
 *   Mr. Ibnu Syena Alfitra (3,96%)  ─┐
 *   PT Indo American Foods (69,24%) ─┼─→ PT Indo American Seafoods Tbk
 *   Mr. Saimi Saleh (5,94%)         ─┤
 *   Masyarakat < 5% (20,86%)        ─┘
 *                                       │
 *                                       └─ 51,22% → PT Indokom Samudera Persada
 * Pengendali terakhir (UBO): Saimi Saleh
 *
 * Diagram ini diterjemahkan jadi tabel edge-list (Pemegang Saham | Kepemilikan
 * Pada | %) supaya datanya terstruktur & bisa dicek/diquery, bukan cuma
 * gambar statis — sama pendekatannya kayak fix Form 1130B.
 *
 * Field generik lama (ownership_structure, subsidiaries_detail,
 * ownership_changes, conclusion — hasil karangan, bukan dari dokumen asli)
 * DIHAPUS karena tidak ada isi setara di sumber dokumen.
 *
 * Jalankan: php artisan db:seed --class=Form1130DEntitiesTreeSeeder
 */
class Form1130DEntitiesTreeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $formId = DB::table('audit_forms')->where('code', '1130D')->value('id');
        if (! $formId) {
            $this->command?->error('Form 1130D tidak ditemukan di audit_forms.');

            return;
        }

        // Bersihin section/field lama — idempotent, aman dijalankan berkali-kali
        $existingSectionIds = DB::table('audit_form_sections')->where('form_id', $formId)->pluck('id');
        DB::table('audit_form_fields')->whereIn('section_id', $existingSectionIds)->delete();
        DB::table('audit_form_sections')->where('form_id', $formId)->delete();

        $sectionId = DB::table('audit_form_sections')->insertGetId([
            'form_id' => $formId,
            'section_name' => 'Entities Tree — Struktur Kepemilikan',
            'section_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $treeColumns = json_encode([
            ['key' => 'pemilik', 'label' => 'Pemegang Saham / Entitas', 'type' => 'text', 'width' => '35%'],
            ['key' => 'dimiliki', 'label' => 'Kepemilikan Pada', 'type' => 'text', 'width' => '35%'],
            ['key' => 'persentase', 'label' => '% Kepemilikan', 'type' => 'number', 'width' => '15%'],
        ]);

        DB::table('audit_form_fields')->insert([
            [
                'section_id' => $sectionId,
                'field_name' => 'ubo_name',
                'field_label' => 'Pengendali Terakhir (Ultimate Beneficial Owner / UBO)',
                'field_type' => 'text',
                'is_required' => true,
                'field_order' => 1,
                'options_json' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_id' => $sectionId,
                'field_name' => 'entities_tree',
                'field_label' => 'Struktur Kepemilikan (Entities Tree) — dari pemegang saham hingga entitas yang diaudit',
                'field_type' => 'repeater',
                'is_required' => true,
                'field_order' => 2,
                'options_json' => $treeColumns,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // Isi data jawaban persis dari diagram di dokumen sumber
        $client = DB::table('clients')->where('name', 'like', '%Indo American%')->first();
        $engagement = $client ? DB::table('engagements')->where('client_id', $client->id)->first() : null;

        if ($engagement) {
            $response = DB::table('audit_form_responses')
                ->where('form_id', $formId)
                ->where('engagement_id', $engagement->id)
                ->first();

            // Kalau response belum ada, bikin dulu (mengikuti pola form lain)
            if (! $response) {
                $userId = DB::table('users')->value('id');
                $responseId = DB::table('audit_form_responses')->insertGetId([
                    'form_id' => $formId,
                    'engagement_id' => $engagement->id,
                    'user_id' => $userId,
                    'status' => 'draft',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $response = (object) ['id' => $responseId];
            }

            $uboFieldId = DB::table('audit_form_fields')->where('section_id', $sectionId)->where('field_name', 'ubo_name')->value('id');
            $treeFieldId = DB::table('audit_form_fields')->where('section_id', $sectionId)->where('field_name', 'entities_tree')->value('id');

            DB::table('audit_form_answers')->updateOrInsert(
                ['response_id' => $response->id, 'field_id' => $uboFieldId],
                ['response_value' => 'Saimi Saleh', 'created_at' => $now, 'updated_at' => $now]
            );

            DB::table('audit_form_answers')->updateOrInsert(
                ['response_id' => $response->id, 'field_id' => $treeFieldId],
                ['response_value' => json_encode([
                    ['pemilik' => 'Mr. Ibnu Syena Alfitra', 'dimiliki' => 'PT Indo American Seafoods Tbk', 'persentase' => 3.96],
                    ['pemilik' => 'PT Indo American Foods', 'dimiliki' => 'PT Indo American Seafoods Tbk', 'persentase' => 69.24],
                    ['pemilik' => 'Mr. Saimi Saleh', 'dimiliki' => 'PT Indo American Seafoods Tbk', 'persentase' => 5.94],
                    ['pemilik' => 'Masyarakat (masing-masing dibawah 5%)', 'dimiliki' => 'PT Indo American Seafoods Tbk', 'persentase' => 20.86],
                    ['pemilik' => 'PT Indo American Seafoods Tbk', 'dimiliki' => 'PT Indokom Samudera Persada', 'persentase' => 51.22],
                ]), 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $this->command?->info('Form 1130D: Entities Tree berhasil di-seed sesuai diagram dokumen sumber (5 baris kepemilikan, UBO: Saimi Saleh).');
    }
}
