<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuditSystemSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $roles = [
            ['name' => 'Admin', 'description' => 'Kelola user dan master data'],
            ['name' => 'Partner', 'description' => 'Approval final'],
            ['name' => 'Manager', 'description' => 'Review dan approval awal'],
            ['name' => 'Senior', 'description' => 'Review dan monitoring'],
            ['name' => 'Junior', 'description' => 'Input dan submit form'],
        ];
        foreach ($roles as &$role) {
            $role['created_at'] = $now;
            $role['updated_at'] = $now;
        }
        DB::table('roles')->insert($roles);

        $roleIds = DB::table('roles')->pluck('id', 'name');
        $password = Hash::make('password123');
        $users = [
            ['role_id' => $roleIds['Admin'], 'name' => 'Admin KAP MGN', 'email' => 'admin@kapmgn.test'],
            ['role_id' => $roleIds['Partner'], 'name' => 'Partner Audit', 'email' => 'partner@kapmgn.test'],
            ['role_id' => $roleIds['Manager'], 'name' => 'Manager Audit', 'email' => 'manager@kapmgn.test'],
            ['role_id' => $roleIds['Senior'], 'name' => 'Senior Auditor', 'email' => 'senior@kapmgn.test'],
            ['role_id' => $roleIds['Junior'], 'name' => 'Junior Auditor', 'email' => 'junior@kapmgn.test'],
        ];
        foreach ($users as &$user) {
            $user['password'] = $password;
            $user['created_at'] = $now;
            $user['updated_at'] = $now;
        }
        DB::table('users')->insert($users);

        $clientId = DB::table('clients')->insertGetId([
            'name' => 'PT Indo American Seafoods Tbk',
            'client_type' => 'TBK',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $engagementId = DB::table('engagements')->insertGetId([
            'client_id' => $clientId,
            'engagement_code' => 'IAS-2024',
            'engagement_year' => 2024,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $kopiClientId = DB::table('clients')->insertGetId([
            'name' => 'PT Kopi',
            'client_type' => 'Non-TBK',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $kopiEngagementId = DB::table('engagements')->insertGetId([
            'client_id' => $kopiClientId,
            'engagement_code' => 'KOPI-2026',
            'engagement_year' => 2026,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $forms = [
            ['code' => '1000', 'name' => 'Risk Assessment', 'form_type' => 'parent'],
            ['code' => '1100', 'name' => 'Memo Penerimaan & Keberlanjutan Klien', 'form_type' => 'child'],
            ['code' => '1110', 'name' => 'Survey Klien', 'form_type' => 'child'],
            ['code' => '1120', 'name' => 'Surat Keberatan Professional', 'form_type' => 'child'],
            ['code' => '1130', 'name' => 'Evaluasi Independensi', 'form_type' => 'child'],
            ['code' => '2000', 'name' => 'Risk Response', 'form_type' => 'parent'],
            ['code' => '2100', 'name' => 'Strategi Audit', 'form_type' => 'child'],
            ['code' => '2110', 'name' => 'Komunikasi Tim Audit', 'form_type' => 'child'],
            ['code' => '2200', 'name' => 'Uji Pengendalian', 'form_type' => 'child'],
            ['code' => '2300', 'name' => 'Materiality Sampling dan MUS', 'form_type' => 'child'],
            ['code' => '2400', 'name' => 'Pemeriksaan Informasi Teknologi', 'form_type' => 'child'],
            ['code' => '3000', 'name' => 'Audit Evidence and Documentation', 'form_type' => 'parent'],
            ['code' => '3100', 'name' => 'Balance Sheet', 'form_type' => 'child'],
            ['code' => '4000', 'name' => 'Representation and Consultation', 'form_type' => 'parent'],
            ['code' => '4100', 'name' => 'Konsultasi dengan Partner', 'form_type' => 'child'],
            ['code' => '4200', 'name' => 'Surat Representasi', 'form_type' => 'child'],
            ['code' => '4300', 'name' => 'Konsultasi dengan Penggunaan Report Kertas Kerja', 'form_type' => 'child'],
            ['code' => '4400', 'name' => 'Konsultasi Penggunaan Report Kertas Kerja Ahli', 'form_type' => 'child'],
            ['code' => '5000', 'name' => 'Reporting', 'form_type' => 'parent'],
            ['code' => '5100', 'name' => 'Laporan Arus Kas', 'form_type' => 'child'],
            ['code' => '5200', 'name' => 'CaLK', 'form_type' => 'child'],
            ['code' => '5903', 'name' => 'Finalisasi', 'form_type' => 'child'],
        ];
        foreach ($forms as &$form) {
            $form['parent_form_id'] = null;
            $form['created_at'] = $now;
            $form['updated_at'] = $now;
        }
        DB::table('audit_forms')->insert($forms);

        $formIds = DB::table('audit_forms')->pluck('id', 'code');
        DB::table('audit_forms')->whereIn('code', ['1100', '1110', '1120', '1130'])->update(['parent_form_id' => $formIds['1000']]);
        DB::table('audit_forms')->whereIn('code', ['2100', '2110', '2200', '2300', '2400'])->update(['parent_form_id' => $formIds['2000']]);
        DB::table('audit_forms')->where('code', '3100')->update(['parent_form_id' => $formIds['3000']]);
        DB::table('audit_forms')->whereIn('code', ['4100', '4200', '4300', '4400'])->update(['parent_form_id' => $formIds['4000']]);
        DB::table('audit_forms')->whereIn('code', ['5100', '5200', '5903'])->update(['parent_form_id' => $formIds['5000']]);

        foreach (['Partner' => 'partner@kapmgn.test', 'Manager' => 'manager@kapmgn.test', 'Senior' => 'senior@kapmgn.test', 'Junior' => 'junior@kapmgn.test'] as $role => $email) {
            $userId = DB::table('users')->where('email', $email)->value('id');
            DB::table('audit_assignments')->insert([
                'engagement_id' => $engagementId,
                'user_id' => $userId,
                'role_id' => $roleIds[$role],
                'assigned_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('audit_assignments')->insert([
                'engagement_id' => $kopiEngagementId,
                'user_id' => $userId,
                'role_id' => $roleIds[$role],
                'assigned_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ----------------------------------------------------
        // SEED SECTIONS & FIELDS: FORM 1110 (SURVEY KLIEN)
        // ----------------------------------------------------
        $form1110Id = $formIds['1110'];
        
        $sec1110_1 = DB::table('audit_form_sections')->insertGetId([
            'form_id' => $form1110Id,
            'section_name' => '1. Informasi Umum Entitas',
            'section_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('audit_form_fields')->insert([
            [
                'section_id' => $sec1110_1,
                'field_name' => 'client_legal_name',
                'field_label' => 'Nama Resmi Entitas / Perusahaan',
                'field_type' => 'text',
                'is_required' => true,
                'field_order' => 1,
                'options_json' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_id' => $sec1110_1,
                'field_name' => 'business_nature',
                'field_label' => 'Bidang Usaha & Aktivitas Utama',
                'field_type' => 'textarea',
                'is_required' => true,
                'field_order' => 2,
                'options_json' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_id' => $sec1110_1,
                'field_name' => 'accounting_framework',
                'field_label' => 'Kerangka Pelaporan Keuangan yang Digunakan (misal: SAK/PSAK)',
                'field_type' => 'text',
                'is_required' => true,
                'field_order' => 3,
                'options_json' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $sec1110_2 = DB::table('audit_form_sections')->insertGetId([
            'form_id' => $form1110Id,
            'section_name' => '2. Struktur Manajemen & Kepemilikan',
            'section_order' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('audit_form_fields')->insert([
            [
                'section_id' => $sec1110_2,
                'field_name' => 'key_management',
                'field_label' => 'Daftar Manajemen Kunci (Direksi & Komisaris)',
                'field_type' => 'textarea',
                'is_required' => true,
                'field_order' => 1,
                'options_json' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_id' => $sec1110_2,
                'field_name' => 'ultimate_owner',
                'field_label' => 'Pemilik Manfaat Akhir (Ultimate Beneficial Owner)',
                'field_type' => 'text',
                'is_required' => false,
                'field_order' => 2,
                'options_json' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // ----------------------------------------------------
        // SEED SECTIONS & FIELDS: FORM 1130 (EVALUASI INDEPENDENSI)
        // ----------------------------------------------------
        $form1130Id = $formIds['1130'];

        $sec1130_1 = DB::table('audit_form_sections')->insertGetId([
            'form_id' => $form1130Id,
            'section_name' => 'Evaluasi Ancaman Independensi Tim Perikatan',
            'section_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('audit_form_fields')->insert([
            [
                'section_id' => $sec1130_1,
                'field_name' => 'financial_interest',
                'field_label' => 'Apakah ada anggota tim yang memiliki kepentingan keuangan langsung pada klien?',
                'field_type' => 'dropdown',
                'is_required' => true,
                'field_order' => 1,
                'options_json' => json_encode([
                    ['value' => 'Tidak Ada', 'label' => 'Tidak Ada'],
                    ['value' => 'Ada', 'label' => 'Ada (Jelaskan di Catatan)'],
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_id' => $sec1130_1,
                'field_name' => 'family_relationship',
                'field_label' => 'Apakah ada hubungan keluarga dekat antara tim audit dan pejabat kunci klien?',
                'field_type' => 'dropdown',
                'is_required' => true,
                'field_order' => 2,
                'options_json' => json_encode([
                    ['value' => 'Tidak Ada', 'label' => 'Tidak Ada'],
                    ['value' => 'Ada', 'label' => 'Ada (Jelaskan di Catatan)'],
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_id' => $sec1130_1,
                'field_name' => 'independence_conclusion',
                'field_label' => 'Kesimpulan Independensi Tim Audit',
                'field_type' => 'textarea',
                'is_required' => true,
                'field_order' => 3,
                'options_json' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
