<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Fase1000FieldsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $ynOptions = json_encode([
            ['value' => 'Y', 'label' => 'Ya / Yes'],
            ['value' => 'N', 'label' => 'Tidak / No'],
            ['value' => 'NA', 'label' => 'N/A'],
        ]);

        $seedForm = function (string $formCode, array $sectionsData) use ($now, $ynOptions): void {
            $formId = DB::table('audit_forms')->where('code', $formCode)->value('id');
            if (! $formId) {
                return;
            }

            $existingSectionIds = DB::table('audit_form_sections')->where('form_id', $formId)->pluck('id');
            DB::table('audit_form_fields')->whereIn('section_id', $existingSectionIds)->delete();
            DB::table('audit_form_sections')->where('form_id', $formId)->delete();

            $sectionOrder = 0;
            foreach ($sectionsData as $section) {
                $sectionOrder++;
                $sectionId = DB::table('audit_form_sections')->insertGetId([
                    'form_id' => $formId,
                    'section_name' => $section['title'],
                    'section_order' => $sectionOrder,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $fieldOrder = 0;
                foreach ($section['fields'] as $field) {
                    $fieldOrder++;
                    DB::table('audit_form_fields')->insert([
                        'section_id' => $sectionId,
                        'field_name' => $field['name'],
                        'field_label' => $field['label'],
                        'field_type' => $field['type'] ?? 'text',
                        'is_required' => $field['required'] ?? true,
                        'field_order' => $fieldOrder,
                        'options_json' => $field['options'] ?? ($field['type'] === 'dropdown' ? $ynOptions : null),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        };

        // =====================================================
        // FORM 1120: SURAT KEBERATAN PROFESIONAL
        // Sumber: 1120 Surat Keberatan Professional IAS 2024
        // Isi: Pernyataan naratif tentang ada/tidak surat keberatan
        // =====================================================
        $seedForm('1120', [
            [
                'title' => 'Surat Keberatan Profesional',
                'fields' => [
                    ['name' => 'objection_statement', 'label' => 'Pernyataan Surat Keberatan Profesional (Jelaskan apakah ada surat keberatan profesional. Jika tidak ada, jelaskan alasannya termasuk informasi KAP/AP terdahulu)', 'type' => 'textarea'],
                ],
            ],
        ]);

        // =====================================================
        // FORM 1200: KONFIRMASI INDEPENDENSI TIM PERIKATAN
        // Sumber: 1200 Konfirmasi Independen PT IAS 2024 Rev 1.docx
        // Tabel: No | Anggota Perikatan | Jabatan | Tanggal Konfirmasi | Paraf
        // =====================================================
        $seedForm('1200', [
            [
                'title' => 'Pernyataan Konfirmasi Independensi',
                'fields' => [
                    ['name' => 'confirmation_statement', 'label' => 'Pernyataan: Saya mengkonfirmasi bahwa (i) saya/suami/istri/tanggungan/anak-anak/saudara kandung tidak memiliki kepentingan keuangan pada klien ini, dan (ii) independensi saya dapat dipertanggungjawabkan dalam hal hubungan pribadi dan konflik kepentingan', 'type' => 'textarea'],
                    ['name' => 'team_declarations', 'label' => 'Daftar Anggota Tim Perikatan Audit beserta Jabatan dan Tanggal Konfirmasi (format: Nama | Jabatan | Tanggal | Paraf)', 'type' => 'textarea'],
                ],
            ],
        ]);

        // =====================================================
        // FORM 1210: KUISIONER INDEPENDENSI
        // Sumber: 1210 Kuisioner Independen PT IAS 2024 Rev 1.docx
        // 3 Bagian: Ringkasan Jasa, Identifikasi Ancaman, Kesimpulan
        // =====================================================
        $seedForm('1210', [
            [
                'title' => 'Bagian 1: Ringkasan Jasa yang Diberikan',
                'fields' => [
                    ['name' => 'q1_public_company', 'label' => '1. Apakah entitas merupakan perusahaan terbuka? Jika Ya, jelaskan', 'type' => 'dropdown'],
                    ['name' => 'q1_comment', 'label' => 'Komentar No. 1', 'type' => 'textarea', 'required' => false],
                    ['name' => 'q2_other_services', 'label' => '2. Apakah ada jasa lain selain jasa audit yang akan diberikan?', 'type' => 'dropdown'],
                    ['name' => 'q2_comment', 'label' => 'Komentar No. 2', 'type' => 'textarea', 'required' => false],
                    ['name' => 'q3_contingent_fees', 'label' => '3. Apakah ada fee bersyarat, referral fees, atau fee lainnya?', 'type' => 'dropdown'],
                    ['name' => 'q3_comment', 'label' => 'Komentar No. 3', 'type' => 'textarea', 'required' => false],
                ],
            ],
            [
                'title' => 'Bagian 2: Identifikasi Ancaman',
                'fields' => [
                    ['name' => 'q4_self_review', 'label' => '4. Self Review: Apakah ada ancaman self review?', 'type' => 'dropdown'],
                    ['name' => 'q4_comment', 'label' => 'Komentar No. 4', 'type' => 'textarea', 'required' => false],
                    ['name' => 'q5_self_interest', 'label' => '5. Self Interest: Apakah ada ancaman self interest?', 'type' => 'dropdown'],
                    ['name' => 'q5_comment', 'label' => 'Komentar No. 5', 'type' => 'textarea', 'required' => false],
                ],
            ],
            [
                'title' => 'Bagian 3: Kesimpulan',
                'fields' => [
                    ['name' => 'q6_code_compliance', 'label' => '1. Apakah kita telah mematuhi Kode Etik Akuntan Publik sesuai dengan Seksi 290 dari Kode Etik Profesi Akuntan Publik?', 'type' => 'dropdown'],
                    ['name' => 'q6_comment', 'label' => 'Komentar No. 1 Kesimpulan', 'type' => 'textarea', 'required' => false],
                    ['name' => 'q7_adequate_procedures', 'label' => '2. Kecukupan prosedur telah dilakukan untuk mengeliminasi adanya ancaman dalam penerimaan klien dan menyatakan bahwa kita ada pada level independen yang memadai untuk menerima klien', 'type' => 'dropdown'],
                    ['name' => 'q7_comment', 'label' => 'Komentar No. 2 Kesimpulan', 'type' => 'textarea', 'required' => false],
                ],
            ],
        ]);

        // =====================================================
        // FORM 1400: LAPORAN RISIKO
        // Sumber: 1400 Laporan Risiko PT IAS 2024.docx
        // Tabel: No | Deskripsi Risiko | Kesalahan | Dampak | Probabilitas | Risiko Signifikan | Asersi | Ringkasan Pendekatan | Ref
        // 2 bagian: Tingkat Laporan Keuangan + Tingkat Akun
        // =====================================================
        $riskColumns = json_encode([
            ['value' => 'H', 'label' => 'High'],
            ['value' => 'L', 'label' => 'Low'],
        ]);
        $ynSimple = json_encode([
            ['value' => 'Y', 'label' => 'Ya'],
            ['value' => 'N', 'label' => 'Tidak'],
        ]);

        $seedForm('1400', [
            [
                'title' => 'Tingkat Laporan Keuangan (Financial Statement Level)',
                'fields' => [
                    ['name' => 'fs_governance', 'label' => 'Siapa yang bertanggung jawab atas tata kelola Perusahaan, kualifikasi, independensi, dan bagaimana mereka menjalankan fungsinya', 'type' => 'textarea'],
                    ['name' => 'fs_tone_at_top', 'label' => 'Code of Conduct Perusahaan yang mencerminkan penerapan etika Tone at the Top', 'type' => 'textarea'],
                    ['name' => 'fs_management_override', 'label' => 'Apakah ada kontrol untuk menghindari management override (manipulasi pencatatan akuntansi dan kecurangan LK)', 'type' => 'textarea'],
                    ['name' => 'fs_revenue_recognition', 'label' => 'Tes pengendalian terhadap pengakuan pendapatan', 'type' => 'textarea'],
                    ['name' => 'fs_journal_entries', 'label' => 'Entri jurnal Non-standard: Untuk memastikan entri jurnal non-standard berada dalam kegiatan bisnis biasa', 'type' => 'textarea'],
                    ['name' => 'fs_accounting_policies', 'label' => 'Penerapan kebijakan akuntansi sesuai dengan Standar Akuntansi', 'type' => 'textarea'],
                    ['name' => 'fs_bonus_incentives', 'label' => 'Pemberian bonus dan insentif yang mendorong terjadinya manipulasi dalam LK', 'type' => 'textarea'],
                    ['name' => 'fs_personnel_competence', 'label' => 'Kecukupan dan kompetensi personel bidang akuntansi dalam penyusunan LK', 'type' => 'textarea'],
                ],
            ],
            [
                'title' => 'Tingkat Akun (Account Level Risks)',
                'fields' => [
                    ['name' => 'acct_risk_1', 'label' => 'Accounting Risk #1: Pencatatan cadangan kerugian penurunan nilai piutang (deskripsi kesalahan, dampak H/L, probabilitas H/L, risiko signifikan Y/N, asersi, ringkasan pendekatan audit)', 'type' => 'textarea'],
                    ['name' => 'acct_risk_2', 'label' => 'Accounting Risk #2: Pencatatan PSAK 24 tidak sesuai siaran pers IAI (deskripsi, dampak, probabilitas, asersi, pendekatan)', 'type' => 'textarea'],
                    ['name' => 'acct_risk_3', 'label' => 'Fraud Risk #1: Efektifitas Perusahaan dalam menjaga asetnya (deskripsi, dampak, probabilitas, asersi, pendekatan)', 'type' => 'textarea'],
                    ['name' => 'acct_risk_4', 'label' => 'Fraud Risk #2: Penyalahgunaan aset oleh karyawan dan manajemen (deskripsi, dampak, probabilitas, asersi, pendekatan)', 'type' => 'textarea'],
                    ['name' => 'acct_risk_5', 'label' => 'Accounting Risk #3 / Fraud Risk #3: Fraudulent Financial Reporting (deskripsi, dampak, probabilitas, asersi, pendekatan)', 'type' => 'textarea'],
                ],
            ],
        ]);

        // =====================================================
        // FORM 1460: PENGENDALIAN INTERNAL ENTITAS
        // Sumber: 1460 Pengendalian Internal Entitas PT IAS 2024.docx
        // Tabel: No | Prosedur Audit | Y/N/NA | Komentar
        // =====================================================
        $seedForm('1460', [
            [
                'title' => 'Penilaian Pengendalian Internal Entitas',
                'fields' => [
                    ['name' => 'q1_internal_audit_adequate', 'label' => '1. Apakah pekerjaan internal auditor memadai? (Pertimbangan: tujuan fungsi internal audit, kompetensi teknis, profesionalitas, supervisi)', 'type' => 'dropdown'],
                    ['name' => 'q1_comment', 'label' => 'Komentar / Ref No. 1', 'type' => 'textarea', 'required' => false],
                    ['name' => 'q2_usable_as_evidence', 'label' => '2. Menentukan apakah hasil pekerjaan internal audit dapat digunakan sebagai bukti audit eksternal? (sifat dan ruang lingkup, penilaian risiko salah saji material)', 'type' => 'dropdown'],
                    ['name' => 'q2_comment', 'label' => 'Komentar / Ref No. 2', 'type' => 'textarea', 'required' => false],
                    ['name' => 'q3_adequate_for_external', 'label' => '3. Apakah pekerjaan yang dilakukan internal auditor cukup memadai untuk tujuan eksternal auditor?', 'type' => 'dropdown'],
                    ['name' => 'q3_comment', 'label' => 'Komentar / Ref No. 3', 'type' => 'textarea', 'required' => false],
                    ['name' => 'q4_conclusion', 'label' => '4. Jika Ya, dokumentasikan kesimpulan tentang evaluasi kecukupan menggunakan hasil pekerjaan internal auditor dan prosedur audit yang akan dipakai oleh eksternal auditor', 'type' => 'textarea'],
                    ['name' => 'q4_comment', 'label' => 'Komentar / Ref No. 4', 'type' => 'textarea', 'required' => false],
                ],
            ],
        ]);

        // =====================================================
        // FORM 1500: PENILAIAN RISIKO TINGKAT LK PER AKUN
        // Sumber: 1500 Penilaian Risiko Tingkat LK - Per Akun PT IAS 2024.docx
        // Tabel: Akun | Nilai Unaudited | Material Y/N | Assertion | Risk Assessment | Alasan | Strategi
        // =====================================================
        $seedForm('1500', [
            [
                'title' => 'Informasi Materialitas',
                'fields' => [
                    ['name' => 'overall_materiality_info', 'label' => 'Overall Materiality dan Planned Performance Materiality (tuliskan nilai dan persentase yang digunakan)', 'type' => 'textarea'],
                ],
            ],
            [
                'title' => 'Penilaian Risiko Per Akun (isi per baris: Akun | Nilai Unaudited Rp | Material Y/N | Assertion C/A/V/E/P | Risk H/M/L | Alasan | Strategi Substantive)',
                'fields' => [
                    ['name' => 'acct_kas_bank', 'label' => 'Kas dan Setara Kas', 'type' => 'textarea'],
                    ['name' => 'acct_piutang_usaha', 'label' => 'Piutang Usaha dari Pihak Ketiga', 'type' => 'textarea'],
                    ['name' => 'acct_piutang_lain', 'label' => 'Piutang Lain-lain dari Pihak Berelasi', 'type' => 'textarea'],
                    ['name' => 'acct_persediaan', 'label' => 'Persediaan', 'type' => 'textarea'],
                    ['name' => 'acct_aset_biologis', 'label' => 'Aset Biologis', 'type' => 'textarea'],
                    ['name' => 'acct_uang_muka', 'label' => 'Uang Muka dan Biaya Dibayar Dimuka', 'type' => 'textarea'],
                    ['name' => 'acct_pajak_dimuka', 'label' => 'Pajak Dibayar di Muka', 'type' => 'textarea'],
                    ['name' => 'acct_aset_tetap', 'label' => 'Aset Tetap', 'type' => 'textarea'],
                    ['name' => 'acct_aset_hak_guna', 'label' => 'Aset Hak-Guna', 'type' => 'textarea'],
                    ['name' => 'acct_pajak_tangguhan', 'label' => 'Aset Pajak Tangguhan', 'type' => 'textarea'],
                    ['name' => 'acct_utang_usaha', 'label' => 'Utang Usaha kepada Pihak Ketiga', 'type' => 'textarea'],
                    ['name' => 'acct_beban_akrual', 'label' => 'Beban Akrual', 'type' => 'textarea'],
                    ['name' => 'acct_utang_pajak', 'label' => 'Utang Pajak', 'type' => 'textarea'],
                    ['name' => 'acct_utang_bank', 'label' => 'Utang Bank', 'type' => 'textarea'],
                    ['name' => 'acct_utang_aset_tetap', 'label' => 'Utang Pembelian Aset Tetap', 'type' => 'textarea'],
                    ['name' => 'acct_imbalan_pascakerja', 'label' => 'Liabilitas Imbalan Pascakerja', 'type' => 'textarea'],
                    ['name' => 'acct_modal', 'label' => 'Modal', 'type' => 'textarea'],
                    ['name' => 'acct_tambahan_modal', 'label' => 'Tambahan Modal Disetor', 'type' => 'textarea'],
                    ['name' => 'acct_oci', 'label' => 'Penghasilan Komprehensif Lain', 'type' => 'textarea'],
                    ['name' => 'acct_saldo_laba', 'label' => 'Saldo Laba', 'type' => 'textarea'],
                    ['name' => 'acct_penjualan', 'label' => 'Penjualan', 'type' => 'textarea'],
                    ['name' => 'acct_cogs', 'label' => 'Beban Pokok Penjualan', 'type' => 'textarea'],
                    ['name' => 'acct_beban_umum', 'label' => 'Beban Umum dan Administrasi', 'type' => 'textarea'],
                    ['name' => 'acct_beban_penjualan', 'label' => 'Beban Penjualan', 'type' => 'textarea'],
                    ['name' => 'acct_pendapatan_lain', 'label' => 'Pendapatan (Beban) Lain-lain', 'type' => 'textarea'],
                    ['name' => 'acct_beban_bunga', 'label' => 'Beban Bunga dan Keuangan', 'type' => 'textarea'],
                ],
            ],
        ]);

        // =====================================================
        // FORM 1600: PENENTUAN MATERIALITAS
        // Sumber: 1600 Menentukan Materialitas_PT IAS_Des 2024.docx
        // I. Overall Materiality, II. Threshold, III. Kuantitatif, IV. MUD
        // =====================================================
        $seedForm('1600', [
            [
                'title' => 'I. Overall Materiality',
                'fields' => [
                    ['name' => 'main_users', 'label' => '1. Mengidentifikasi pengguna utama laporan keuangan', 'type' => 'textarea'],
                    ['name' => 'qualitative_factors', 'label' => '2. Menjelaskan faktor kualitatif dalam penentuan materialitas', 'type' => 'textarea'],
                ],
            ],
            [
                'title' => 'II. Menentukan Threshold',
                'fields' => [
                    ['name' => 'benchmark_aset_pct', 'label' => 'Benchmark Aset — Rentang 0.5%-2% — Persentase yang Diterapkan', 'type' => 'text', 'required' => false],
                    ['name' => 'benchmark_pendapatan_pct', 'label' => 'Benchmark Pendapatan — Rentang 0.5%-2% — Persentase yang Diterapkan', 'type' => 'text', 'required' => false],
                    ['name' => 'benchmark_aset_bersih_pct', 'label' => 'Benchmark Aset Bersih — Rentang 3%-5% — Persentase yang Diterapkan', 'type' => 'text', 'required' => false],
                    ['name' => 'benchmark_ebit_pct', 'label' => 'Benchmark Normalisasi Laba Sebelum Pajak — Rentang 5%-10% — Persentase yang Diterapkan', 'type' => 'text', 'required' => false],
                    ['name' => 'benchmark_other', 'label' => 'Benchmark Lain-lain', 'type' => 'text', 'required' => false],
                    ['name' => 'benchmark_justification', 'label' => 'Alasan/Justifikasi memilih benchmark dan persentase yang diterapkan', 'type' => 'textarea'],
                ],
            ],
            [
                'title' => 'III. Menentukan Kuantitatif Materialitas',
                'fields' => [
                    ['name' => 'ebit_current', 'label' => 'Total EBIT — Tidak Diaudit (Periode Saat Ini - Rp)', 'type' => 'currency'],
                    ['name' => 'ebit_prior', 'label' => 'Total EBIT — Tidak Diaudit (Periode Tahun Lalu - Rp)', 'type' => 'currency', 'required' => false],
                    ['name' => 'overall_materiality_value', 'label' => 'Overall Materiality (nilai dalam Rp)', 'type' => 'currency'],
                    ['name' => 'performance_materiality_pct', 'label' => 'Performance Materiality — persentase dari Overall Materiality (contoh: 80% for low risk)', 'type' => 'text'],
                    ['name' => 'performance_materiality_value', 'label' => 'Performance Materiality (nilai dalam Rp)', 'type' => 'currency'],
                    ['name' => 'specific_pm_qualitative', 'label' => 'Specific Performance Materiality — Apakah ada faktor kualitatif yang memerlukan performance materiality lebih rendah?', 'type' => 'dropdown'],
                    ['name' => 'specific_pm_detail', 'label' => 'Jika Ya, daftar area dan tentukan Specific Performance Materiality', 'type' => 'textarea', 'required' => false],
                ],
            ],
            [
                'title' => 'IV. Summary of Unadjusted Misstatement (MUD)',
                'fields' => [
                    ['name' => 'mud_threshold', 'label' => 'Amount below which misstatements are not carried to MUD (5% of Overall Materiality, before tax) — dalam Rp', 'type' => 'currency'],
                ],
            ],
        ]);

        // =====================================================
        // FORM 1700: ALOKASI JAM JASA & PERENCANAAN AUDIT
        // Sumber: 1700 Alokasi Jam Jasa & Perencanaan Audit_PT IAS_2024.xlsx
        // =====================================================
        $seedForm('1700', [
            [
                'title' => 'Informasi Perikatan',
                'fields' => [
                    ['name' => 'nama_kap', 'label' => 'Nama KAP', 'type' => 'text'],
                    ['name' => 'nama_ap', 'label' => 'Nama AP', 'type' => 'text'],
                    ['name' => 'nama_klien', 'label' => 'Nama Klien', 'type' => 'text'],
                    ['name' => 'alamat_klien', 'label' => 'Alamat Klien', 'type' => 'textarea'],
                    ['name' => 'tahun_buku', 'label' => 'Tahun Buku', 'type' => 'text'],
                ],
            ],
            [
                'title' => 'Rencana Jadwal Pelaksanaan',
                'fields' => [
                    ['name' => 'tanggal_mulai', 'label' => 'Tanggal Mulai', 'type' => 'date'],
                    ['name' => 'tanggal_akhir', 'label' => 'Tanggal Akhir', 'type' => 'date'],
                    ['name' => 'jam_mulai', 'label' => 'Jam Mulai Kerja', 'type' => 'text'],
                    ['name' => 'jam_pulang', 'label' => 'Jam Pulang', 'type' => 'text'],
                    ['name' => 'hari_libur', 'label' => 'Jumlah Hari Libur (Sabtu-Minggu + Libur Lain)', 'type' => 'number'],
                    ['name' => 'jam_efektif_per_hari', 'label' => 'Jumlah Jam Kerja Efektif per Hari', 'type' => 'number'],
                ],
            ],
            [
                'title' => 'Susunan Tim Perikatan & Alokasi Jam',
                'fields' => [
                    ['name' => 'team_allocation', 'label' => 'Susunan Tim & Alokasi Jam Jasa per Tim pada setiap tahapan audit (format: No | Nama | Peran | Jam Perencanaan | Jam Risk Response | Jam Reporting)', 'type' => 'textarea'],
                ],
            ],
        ]);

        // =====================================================
        // FORM 1900: KOMUNIKASI TIM PERIKATAN
        // Sumber: 1900 Komunikasi Tim Perikatan_PT IAS_2024.docx
        // Isi: Pekerjaan & Laporan, Pengelolaan (pendekatan ISA), Identifikasi Risiko, Jadwal
        // =====================================================
        $seedForm('1900', [
            [
                'title' => 'Pekerjaan dan Laporan Hasil Pekerjaan',
                'fields' => [
                    ['name' => 'work_description', 'label' => 'Pekerjaan (contoh: Audit Umum)', 'type' => 'text'],
                    ['name' => 'report_output', 'label' => 'Laporan Hasil Pekerjaan (contoh: Laporan Auditor Independen)', 'type' => 'textarea'],
                ],
            ],
            [
                'title' => 'Pengelolaan Pekerjaan — Pendekatan Audit Berbasis ISA',
                'fields' => [
                    ['name' => 'risk_assessment_approach', 'label' => '1. Penilaian Risiko Audit (Pra Penugasan, Perencanaan Audit, Prosedur Penilaian Risiko — tujuan masing-masing)', 'type' => 'textarea'],
                    ['name' => 'risk_response_approach', 'label' => '2. Menanggapi Risiko Audit (Rancangan tanggapan, Implementasi terhadap risiko salah saji material)', 'type' => 'textarea'],
                    ['name' => 'reporting_approach', 'label' => '3. Pelaporan Audit (Evaluasi bukti audit, Implementasi tanggapan, Rumuskan opini)', 'type' => 'textarea'],
                ],
            ],
            [
                'title' => 'Identifikasi Awal Risiko Audit',
                'fields' => [
                    ['name' => 'asset_liability_risks', 'label' => '1. Pengakuan, Pengukuran, Klasifikasi Aset dan Liabilities (uraikan per akun: Kas, Piutang, Persediaan, Investasi, Aset Tetap, Utang Bank, Utang Usaha, dll)', 'type' => 'textarea'],
                    ['name' => 'income_expense_risks', 'label' => '2. Pengakuan, Pengukuran, Klasifikasi Pendapatan dan Beban (uraikan: Pendapatan Usaha, Beban Pokok Pendapatan, Beban Umum & Administrasi, Pendapatan/Beban Lain-lain)', 'type' => 'textarea'],
                ],
            ],
            [
                'title' => 'Rencana Jadwal Pelaksanaan Audit',
                'fields' => [
                    ['name' => 'schedule_detail', 'label' => 'Jadwal pelaksanaan audit (tanggal, jam kerja, lokasi)', 'type' => 'textarea'],
                    ['name' => 'team_members', 'label' => 'Jumlah & daftar nama tim yang berangkat (Nama : Jabatan/Role)', 'type' => 'textarea'],
                ],
            ],
        ]);

        $this->command?->info('Fase1000FieldsSeeder: Form 1120, 1200, 1210, 1400, 1460, 1500, 1600, 1700, 1900 berhasil di-seed dari template asli.');
    }
}
