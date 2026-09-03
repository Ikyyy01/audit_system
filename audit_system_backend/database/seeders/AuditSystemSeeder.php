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
            ['code' => '1130A', 'name' => 'Independence Checklist & Conflict Check', 'form_type' => 'child'],
            ['code' => '1130B', 'name' => 'Cek Latar Belakang - First Pass Data', 'form_type' => 'child'],
            ['code' => '1130C', 'name' => 'Background Check - Pihak Berelasi & Profil', 'form_type' => 'child'],
            ['code' => '1130D', 'name' => 'Entities Tree / Struktur Kepemilikan (UBO)', 'form_type' => 'child'],
            ['code' => '1200', 'name' => 'Konfirmasi Independensi', 'form_type' => 'child'],
            ['code' => '1210', 'name' => 'Kuisioner Independensi', 'form_type' => 'child'],
            ['code' => '1400', 'name' => 'Laporan Risiko', 'form_type' => 'child'],
            ['code' => '1410', 'name' => 'Pemahaman Peraturan Relevan', 'form_type' => 'child'],
            ['code' => '1420', 'name' => 'Prosedur Analitik Awal', 'form_type' => 'child'],
            ['code' => '1430', 'name' => 'Proses Pelaporan Keuangan', 'form_type' => 'child'],
            ['code' => '1440', 'name' => 'Fraud Risk Assessment', 'form_type' => 'child'],
            ['code' => '1441', 'name' => 'Penilaian Risiko Kecurangan - Interview Klien', 'form_type' => 'child'],
            ['code' => '1450', 'name' => 'Penilaian Risiko Bisnis', 'form_type' => 'child'],
            ['code' => '1460', 'name' => 'Pengendalian Internal Entitas', 'form_type' => 'child'],
            ['code' => '1500', 'name' => 'Penilaian Risiko Tingkat LK Per Akun', 'form_type' => 'child'],
            ['code' => '1600', 'name' => 'Penentuan Materialitas', 'form_type' => 'child'],
            ['code' => '1610', 'name' => 'Materiality Sampling', 'form_type' => 'child'],
            ['code' => '1700', 'name' => 'Alokasi Jam Jasa & Perencanaan Audit', 'form_type' => 'child'],
            ['code' => '1800', 'name' => 'Surat Tugas', 'form_type' => 'child'],
            ['code' => '1900', 'name' => 'Komunikasi Tim Perikatan', 'form_type' => 'child'],
            ['code' => '2000', 'name' => 'Risk Response', 'form_type' => 'parent'],
            ['code' => '2100', 'name' => 'Strategi Audit', 'form_type' => 'child'],
            ['code' => '2110', 'name' => 'Komunikasi Tim Audit', 'form_type' => 'child'],
            ['code' => '2200', 'name' => 'Uji Pengendalian', 'form_type' => 'child'],
            ['code' => '2300', 'name' => 'Materiality Sampling dan MUS', 'form_type' => 'child'],
            ['code' => '2400', 'name' => 'Pemeriksaan Informasi Teknologi', 'form_type' => 'child'],
            ['code' => '2410', 'name' => 'Pengendalian Umum Komputer', 'form_type' => 'child'],
            ['code' => '2420', 'name' => 'Siklus Bisnis Pengendalian Komputer', 'form_type' => 'child'],
            ['code' => '3000', 'name' => 'Audit Evidence and Documentation', 'form_type' => 'parent'],
            ['code' => '3100', 'name' => 'Balance Sheet', 'form_type' => 'child'],
            ['code' => '4000', 'name' => 'Representation and Consultation', 'form_type' => 'parent'],
            ['code' => '4100', 'name' => 'Konsultasi dengan Partner', 'form_type' => 'child'],
            ['code' => '4200', 'name' => 'Surat Representasi', 'form_type' => 'child'],
            ['code' => '4300', 'name' => 'Konsultasi dengan Penggunaan Report Kertas Kerja', 'form_type' => 'child'],
            ['code' => '4400', 'name' => 'Konsultasi Penggunaan Report Kertas Kerja Ahli', 'form_type' => 'child'],
            ['code' => '5000', 'name' => 'Reporting', 'form_type' => 'parent'],
            ['code' => '5100', 'name' => 'Working Balance Sheet', 'form_type' => 'child'],
            ['code' => '5200', 'name' => 'Confirmation Judgement', 'form_type' => 'child'],
            ['code' => '5300', 'name' => 'Materialitas Final', 'form_type' => 'child'],
            ['code' => '5500', 'name' => 'Checklist Audit', 'form_type' => 'child'],
            ['code' => '5610', 'name' => 'EQCR Checklist', 'form_type' => 'child'],
            ['code' => '5700', 'name' => 'Evaluasi Bukti Audit', 'form_type' => 'child'],
            ['code' => '5900', 'name' => 'Memorandum Audit Final', 'form_type' => 'child'],
            ['code' => '5903', 'name' => 'Checklist Penyelesaian Laporan', 'form_type' => 'child'],
            ['code' => '5904', 'name' => 'Final Independent Auditor Report', 'form_type' => 'child'],
            ['code' => '5905', 'name' => 'Kejadian Setelah Tanggal LK', 'form_type' => 'child'],
            ['code' => '5906', 'name' => 'Kelangsungan Usaha', 'form_type' => 'child'],
            ['code' => '5907', 'name' => 'Komitmen dan Kontinjensi', 'form_type' => 'child'],
            ['code' => '5908', 'name' => 'Informasi Segmen', 'form_type' => 'child'],
        ];
        foreach ($forms as &$form) {
            $form['parent_form_id'] = null;
            $form['created_at'] = $now;
            $form['updated_at'] = $now;
        }
        DB::table('audit_forms')->insert($forms);

        $formIds = DB::table('audit_forms')->pluck('id', 'code');
        DB::table('audit_forms')->whereIn('code', ['1100', '1110', '1120', '1130A', '1130B', '1130C', '1130D', '1200', '1210', '1400', '1410', '1420', '1430', '1440', '1441', '1450', '1460', '1500', '1600', '1610', '1700', '1800', '1900'])->update(['parent_form_id' => $formIds['1000']]);
        DB::table('audit_forms')->whereIn('code', ['2100', '2110', '2200', '2300', '2400', '2410', '2420'])->update(['parent_form_id' => $formIds['2000']]);
        DB::table('audit_forms')->where('code', '3100')->update(['parent_form_id' => $formIds['3000']]);
        DB::table('audit_forms')->whereIn('code', ['4100', '4200', '4300', '4400'])->update(['parent_form_id' => $formIds['4000']]);
        DB::table('audit_forms')->whereIn('code', ['5100', '5200', '5300', '5500', '5610', '5700', '5900', '5903', '5904', '5905', '5906', '5907', '5908'])->update(['parent_form_id' => $formIds['5000']]);

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
        // Sumber: 1110 Survey Klien IAS 2024 Rev 1.docx
        // Tabel Description | Remarks/Explanation (20 baris data)
        // ----------------------------------------------------
        $form1110Id = $formIds['1110'];

        $sec1110 = DB::table('audit_form_sections')->insertGetId([
            'form_id' => $form1110Id,
            'section_name' => 'Survey Klien',
            'section_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $fields1110 = [
            ['name' => 'date_of_survey', 'label' => 'Date of Survey', 'type' => 'date'],
            ['name' => 'venue', 'label' => 'Venue', 'type' => 'text'],
            ['name' => 'survey_time', 'label' => 'Time (From - To)', 'type' => 'time_range'],
            ['name' => 'attendants', 'label' => 'Attendants (Daftar hadir peserta survey)', 'type' => 'textarea'],
            ['name' => 'legal_name', 'label' => 'Legal Name', 'type' => 'text'],
            ['name' => 'scope_of_engagement', 'label' => 'Scope of the Engagement', 'type' => 'text'],
            ['name' => 'financial_accounting_standard', 'label' => 'Financial Accounting Standard', 'type' => 'text'],
            ['name' => 'deliverables', 'label' => 'Deliverables', 'type' => 'text'],
            ['name' => 'objectives', 'label' => 'Objectives', 'type' => 'textarea'],
            ['name' => 'name_of_shareholders', 'label' => 'Name of Shareholders (Susunan pemegang saham)', 'type' => 'textarea'],
            ['name' => 'name_of_management', 'label' => 'Name of the Company\'s Management (BOC and BOD)', 'type' => 'textarea'],
            ['name' => 'ultimate_shareholder', 'label' => 'Ultimate Shareholder', 'type' => 'textarea'],
            ['name' => 'business_activity', 'label' => 'Business Activity of the Company', 'type' => 'textarea'],
            ['name' => 'reporting_currency', 'label' => 'Reporting Currency', 'type' => 'text'],
            ['name' => 'total_assets', 'label' => 'Total Assets (Current Assets, Non-Current Assets, Total)', 'type' => 'textarea'],
            ['name' => 'total_revenues', 'label' => 'Total Revenues', 'type' => 'textarea'],
            ['name' => 'main_customers_vendors', 'label' => 'Main Customers / Main Distributors/Vendors', 'type' => 'textarea'],
            ['name' => 'accounting_system', 'label' => 'Accounting System', 'type' => 'text'],
            ['name' => 'accounting_issues', 'label' => 'Accounting Issues', 'type' => 'textarea'],
            ['name' => 'ethics', 'label' => 'Ethics', 'type' => 'textarea'],
            ['name' => 'conclusion', 'label' => 'Conclusion', 'type' => 'textarea'],
        ];

        $order = 0;
        foreach ($fields1110 as $f) {
            $order++;
            DB::table('audit_form_fields')->insert([
                'section_id' => $sec1110,
                'field_name' => $f['name'],
                'field_label' => $f['label'],
                'field_type' => $f['type'],
                'is_required' => true,
                'field_order' => $order,
                'options_json' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
