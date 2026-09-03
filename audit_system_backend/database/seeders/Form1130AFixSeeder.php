<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Perbaikan khusus Form 1130A supaya PERSIS mengikuti tabel di dokumen sumber:
 * "1130 A. Cek Latar Belakang - Independence Checklist IAS 2024_Rev 1.doc"
 * (3 kolom: No. | P A R T I C U L A R S | Yes/ No/ NA — 5 baris kategori, 13 pertanyaan)
 *
 * Label VERBATIM dari dokumen asli — JANGAN diubah/paraphrase.
 *
 * Jalankan: php artisan db:seed --class=Form1130AFixSeeder
 */
class Form1130AFixSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $formId = DB::table('audit_forms')->where('code', '1130A')->value('id');
        if (! $formId) {
            $this->command?->error('Form 1130A tidak ditemukan di audit_forms.');

            return;
        }

        $ynOptions = json_encode([
            ['value' => 'Y', 'label' => 'Yes'],
            ['value' => 'N', 'label' => 'No'],
            ['value' => 'NA', 'label' => 'N/A'],
        ]);

        // Bersihin dulu — idempotent, aman dijalankan berkali-kali
        $existingSectionIds = DB::table('audit_form_sections')->where('form_id', $formId)->pluck('id');
        DB::table('audit_form_fields')->whereIn('section_id', $existingSectionIds)->delete();
        DB::table('audit_form_sections')->where('form_id', $formId)->delete();

        $sections = [
            [
                'title' => '1. INDEPENDENCE / CONFLICT CHECK CLEARANCE FROM OTHER FUNCTION LINES',
                'fields' => [
                    [
                        'name' => 'q1_independence_check',
                        'label' => 'Have you obtained independence / conflict of interest check results from the other function lines: Magani Gemilang Natama (FAS)? (Not required for recurring audit clients or additional review engagements for an audit client.)',
                    ],
                    [
                        'name' => 'q1_threats_uncovered',
                        'label' => 'Have these procedures uncovered any threats to independence or conflict of interest?',
                    ],
                    [
                        'name' => 'q1_threats_resolved',
                        'label' => 'If YES, have the threats been resolved?',
                    ],
                ],
            ],
            [
                'title' => '2. AUDIT COMMITTEE PRE-APPROVAL (ONLY FOR INDONESIA STOCK EXCHANGE REGISTRANT AND / OR SUBSIDIARIES OF INDONESIA STOCK EXCHANGE REGISTRANT)',
                'fields' => [
                    [
                        'name' => 'q2_audit_committee_preapproval',
                        'label' => 'If MGN is: (a) the Global Auditor of an Indonesia Stock Exchange Registrant; or (b) not the Global Auditor but we audit material subsidiary and the principal auditors rely upon our audit work: Have you obtained pre-approval from the Registrant\'s Audit Committee through the LCSP for the services provided?',
                    ],
                ],
            ],
            [
                'title' => '3. CONSULTATION',
                'fields' => [
                    [
                        'name' => 'q3_consultation',
                        'label' => 'If needed, did the engagement team consult with the Quality Assurance on any independence or conflict of interest issue?',
                    ],
                ],
            ],
            [
                'title' => '4. AUDIT PARTNER ROTATION',
                'fields' => [
                    [
                        'name' => 'q4_partner_rotation',
                        'label' => 'Has the audit partner served this client served for more than 5 years? (Applicable for Public Interest Entity Only)',
                    ],
                    [
                        'name' => 'q4_cooling_period',
                        'label' => 'If No 1 is yes, has there been 5-year cooling period before the Audit Partner serves this client again? (Applicable for Public Interest Entity Only)',
                    ],
                    [
                        'name' => 'q4_eqar_rotation',
                        'label' => 'Has the Individual performing Engagement Quality Assurance Review been assigned for more than 5 years? (Applicable for Public Interest Entity Only)',
                    ],
                    [
                        'name' => 'q4_eqar_cooling',
                        'label' => 'If no 3 is yes, has there been 3-year cooling period before the Audit Partner serves this client again? (Applicable for Public Interest Entity Only)',
                    ],
                ],
            ],
            [
                'title' => '5. Whether they or the body being searched is included in the category',
                'fields' => [
                    ['name' => 'pep_status', 'label' => '1. Politically Exposed Person (PEP)'],
                    ['name' => 'hrc_status', 'label' => '2. High Risk Customers (HRC)'],
                    ['name' => 'hrb_status', 'label' => '3. High Risk Business (HRB)'],
                    ['name' => 'countries_status', 'label' => '4. High Risk Countries (HRC)'],
                ],
            ],
        ];

        $sectionOrder = 0;
        $totalFields = 0;
        foreach ($sections as $section) {
            $sectionOrder++;
            $sectionId = DB::table('audit_form_sections')->insertGetId([
                'form_id' => $formId,
                'section_name' => $section['title'],
                'section_order' => $sectionOrder,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $fieldOrder = 0;
            foreach ($section['fields'] as $f) {
                $fieldOrder++;
                $totalFields++;
                DB::table('audit_form_fields')->insert([
                    'section_id' => $sectionId,
                    'field_name' => $f['name'],
                    'field_label' => $f['label'],
                    'field_type' => 'dropdown',
                    'is_required' => true,
                    'field_order' => $fieldOrder,
                    'options_json' => $ynOptions,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $this->command?->info("Form 1130A: {$sectionOrder} section, {$totalFields} pertanyaan berhasil di-seed (persis mengikuti dokumen sumber).");
    }
}
