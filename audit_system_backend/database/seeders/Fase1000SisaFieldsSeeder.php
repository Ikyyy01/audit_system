<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder untuk form-form Fase 1000 yang belum memiliki sections/fields:
 * 1120 (update/refresh), 1130A, 1130B, 1130C, 1130D,
 * 1410, 1420, 1430, 1440, 1441, 1450, 1610
 *
 * Sumber: file asli di folder "1000 Risk Assesment"
 */
class Fase1000SisaFieldsSeeder extends Seeder
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
                $this->command?->warn("Form {$formCode} tidak ditemukan di audit_forms, skip.");
                return;
            }

            // Hapus sections & fields lama dulu
            $existingSectionIds = DB::table('audit_form_sections')->where('form_id', $formId)->pluck('id');
            DB::table('audit_form_fields')->whereIn('section_id', $existingSectionIds)->delete();
            DB::table('audit_form_sections')->where('form_id', $formId)->delete();

            $sectionOrder = 0;
            foreach ($sectionsData as $section) {
                $sectionOrder++;
                $sectionId = DB::table('audit_form_sections')->insertGetId([
                    'form_id'       => $formId,
                    'section_name'  => $section['title'],
                    'section_order' => $sectionOrder,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);

                $fieldOrder = 0;
                foreach ($section['fields'] as $field) {
                    $fieldOrder++;
                    DB::table('audit_form_fields')->insert([
                        'section_id'   => $sectionId,
                        'field_name'   => $field['name'],
                        'field_label'  => $field['label'],
                        'field_type'   => $field['type'] ?? 'text',
                        'is_required'  => $field['required'] ?? true,
                        'field_order'  => $fieldOrder,
                        'options_json' => $field['options'] ?? (($field['type'] ?? '') === 'dropdown' ? $ynOptions : null),
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ]);
                }
            }

            $total = collect($sectionsData)->sum(fn ($s) => count($s['fields']));
            $this->command?->info("  Form {$formCode}: " . count($sectionsData) . " section, {$total} field.");
        };

        // =====================================================
        // FORM 1120: SURAT KEBERATAN PROFESIONAL (REFRESH)
        // Sumber: 1120 Surat Keberatan Professional IAS 2024 Rev 1.docx
        // Isi: Pernyataan ada/tidak surat keberatan dari KAP/AP terdahulu
        // =====================================================
        $seedForm('1120', [
            [
                'title'  => 'Surat Keberatan Profesional',
                'fields' => [
                    [
                        'name'  => 'has_objection_letter',
                        'label' => 'Apakah terdapat Surat Keberatan Profesional dari KAP/AP terdahulu?',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'     => 'objection_statement',
                        'label'    => 'Penjelasan / Pernyataan Surat Keberatan Profesional (jika tidak ada, jelaskan alasannya termasuk informasi KAP/AP terdahulu dan periode audit sebelumnya)',
                        'type'     => 'textarea',
                        'required' => true,
                    ],
                    [
                        'name'     => 'prior_kap_name',
                        'label'    => 'Nama KAP/AP yang Mengaudit Periode Sebelumnya',
                        'type'     => 'text',
                        'required' => false,
                    ],
                    [
                        'name'     => 'prior_audit_period',
                        'label'    => 'Periode Audit Sebelumnya (contoh: 31 Desember 2023)',
                        'type'     => 'text',
                        'required' => false,
                    ],
                ],
            ],
        ]);

        // =====================================================
        // FORM 1130A: INDEPENDENCE / CONFLICT OF INTEREST CHECKLIST
        // Sumber: 1130 A. Cek Latar Belakang - Independence Checklist IAS 2024_Rev 1.doc
        // Tabel: No | Particulars | Yes/No/NA
        // =====================================================
        $seedForm('1130A', [
            [
                'title'  => 'Independence / Conflict of Interest Procedures Checklist',
                'fields' => [
                    [
                        'name'  => 'q1_independence_check',
                        'label' => '1. INDEPENDENCE / CONFLICT CHECK CLEARANCE FROM OTHER FUNCTION LINES — Have you obtained independence / conflict of interest check results from the other function lines (e.g. Magani Gemilang Natama / FAS)?',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'     => 'q1_threats_uncovered',
                        'label'    => '1a. Apakah prosedur tersebut mengungkap ancaman terhadap independensi atau konflik kepentingan?',
                        'type'     => 'dropdown',
                        'required' => false,
                    ],
                    [
                        'name'     => 'q1_threats_resolved',
                        'label'    => '1b. Jika Ya, apakah ancaman tersebut sudah diselesaikan?',
                        'type'     => 'dropdown',
                        'required' => false,
                    ],
                    [
                        'name'  => 'q2_audit_committee_preapproval',
                        'label' => '2. AUDIT COMMITTEE PRE-APPROVAL (HANYA UNTUK EMITEN BURSA / ANAK PERUSAHAAN EMITEN) — Apakah sudah diperoleh pre-approval dari Komite Audit Emiten untuk jasa yang diberikan?',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'     => 'q2_comment',
                        'label'    => 'Keterangan No. 2',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                    [
                        'name'  => 'q3_consultation',
                        'label' => '3. CONSULTATION — Jika diperlukan, apakah tim perikatan berkonsultasi dengan Quality Assurance terkait masalah independensi atau konflik kepentingan?',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'     => 'q3_comment',
                        'label'    => 'Keterangan No. 3',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                    [
                        'name'  => 'q4_partner_rotation',
                        'label' => '4. AUDIT PARTNER ROTATION — Apakah audit partner telah melayani klien ini lebih dari 5 tahun? (Berlaku untuk Public Interest Entity)',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'     => 'q4_cooling_period',
                        'label'    => '4a. Jika Ya, apakah sudah ada cooling period 5 tahun sebelum Audit Partner melayani klien ini kembali?',
                        'type'     => 'dropdown',
                        'required' => false,
                    ],
                    [
                        'name'  => 'q4_eqar_rotation',
                        'label' => '4b. Apakah individu yang melakukan Engagement Quality Assurance Review ditugaskan lebih dari 5 tahun?',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'     => 'q4_eqar_cooling',
                        'label'    => '4c. Jika Ya, apakah sudah ada cooling period 3 tahun?',
                        'type'     => 'dropdown',
                        'required' => false,
                    ],
                    [
                        'name'  => 'q5_pep_check',
                        'label' => '5. WHETHER INCLUDED IN CATEGORY — Apakah pihak yang diperiksa termasuk dalam kategori: (1) Politically Exposed Person (PEP), (2) High Risk Customers (HRC), (3) High Risk Business (HRB), (4) High Risk Countries?',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'     => 'overall_conclusion',
                        'label'    => 'Kesimpulan keseluruhan atas pemeriksaan independensi dan konflik kepentingan',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                ],
            ],
        ]);

        // =====================================================
        // FORM 1130B: CEK LATAR BELAKANG – FIRST PASS DATA
        // Sumber: 1130 B. Cek Latar Belakang First Pass Data IAS 2024 Rev 1.xlsx
        // Isi: Data identitas perusahaan, pemegang saham, direksi/komisaris, entitas anak
        // =====================================================
        $seedForm('1130B', [
            [
                'title'  => 'Identitas Perusahaan',
                'fields' => [
                    [
                        'name'  => 'company_name',
                        'label' => 'Company Name (Nama Perusahaan)',
                        'type'  => 'text',
                    ],
                    [
                        'name'  => 'company_address',
                        'label' => 'Address (Alamat Perusahaan)',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'industry',
                        'label' => 'Industry (Bidang Usaha)',
                        'type'  => 'text',
                    ],
                    [
                        'name'  => 'incorporated_in',
                        'label' => 'Company Incorporated In (Tempat Pendirian)',
                        'type'  => 'text',
                    ],
                    [
                        'name'  => 'date_of_incorporation',
                        'label' => 'Date of Incorporation (Tanggal Pendirian)',
                        'type'  => 'date',
                    ],
                ],
            ],
            [
                'title'  => 'Pemegang Saham (Stockholders)',
                'fields' => [
                    [
                        'name'  => 'shareholders_info',
                        'label' => 'Struktur Kepemilikan Saham (format: Nama Pemegang Saham | Jumlah Saham | Nilai IDR | % Kepemilikan)',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'     => 'shareholders_background',
                        'label'    => 'Riwayat Singkat Perusahaan & Pemegang Saham (termasuk perubahan anggaran dasar terakhir)',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                ],
            ],
            [
                'title'  => 'Direksi dan Dewan Komisaris',
                'fields' => [
                    [
                        'name'  => 'directors_commissioners',
                        'label' => 'Daftar Direksi dan Dewan Komisaris (format: No | Nama | Jenis Kelamin | No. Identitas | Alamat | Tgl Lahir | Kebangsaan | Jabatan)',
                        'type'  => 'textarea',
                    ],
                ],
            ],
            [
                'title'  => 'Entitas Anak dan Investasi',
                'fields' => [
                    [
                        'name'     => 'subsidiaries',
                        'label'    => 'Daftar Entitas Anak / Subsidiary (format: No | Nama Entitas Anak | Domisili)',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                    [
                        'name'     => 'investments',
                        'label'    => 'Daftar Investasi (format: No | Nama Entitas | Kegiatan Usaha | Tahun Pendirian | Tahun Penyertaan | Domisili | Jumlah Aset | % Kepemilikan)',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                ],
            ],
        ]);

        // =====================================================
        // FORM 1130C: BACKGROUND CHECK
        // Sumber: 1130 C. Background Check_PT IAS Tbk dan Entitas Anak_2024 Rev 1.docx
        // Isi: Daftar nama yang dicari + hasil pencarian + riwayat singkat perusahaan
        // =====================================================
        $seedForm('1130C', [
            [
                'title'  => 'Background Check – Daftar Nama yang Diperiksa',
                'fields' => [
                    [
                        'name'  => 'searched_names',
                        'label' => 'Daftar Nama yang Dicari (Searched Names) — tuliskan satu per baris nama direksi/komisaris/pemegang saham utama yang diperiksa latar belakangnya',
                        'type'  => 'textarea',
                    ],
                ],
            ],
            [
                'title'  => 'Hasil Background Check',
                'fields' => [
                    [
                        'name'  => 'search_results',
                        'label' => 'Hasil Pencarian / Search Results — dokumentasikan temuan per nama yang diperiksa (termasuk ketidakhadiran temuan negatif)',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'company_background',
                        'label' => 'Riwayat Singkat Perusahaan — dokumentasikan profil singkat entitas utama dan entitas anak yang relevan',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'business_purpose',
                        'label' => 'Maksud dan Tujuan serta Kegiatan Usaha — uraikan bidang usaha utama entitas dan entitas anak',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'     => 'conclusion',
                        'label'    => 'Kesimpulan Background Check',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                ],
            ],
        ]);

        // =====================================================
        // FORM 1130D: ENTITIES TREE / STRUKTUR KEPEMILIKAN
        // Sumber: 1130 D. Entities Tree PT IAS 2024 Rev 1.docx
        // Isi: Dokumentasi struktur pemegang saham / UBO (Ultimate Beneficial Owner)
        // =====================================================
        $seedForm('1130D', [
            [
                'title'  => 'Entities Tree – Struktur Kepemilikan',
                'fields' => [
                    [
                        'name'  => 'ubo_name',
                        'label' => 'Pengendali Terakhir / Ultimate Beneficial Owner (UBO) — nama individu atau entitas yang menjadi pengendali akhir perusahaan',
                        'type'  => 'text',
                    ],
                    [
                        'name'  => 'ownership_structure',
                        'label' => 'Struktur Kepemilikan / Entities Tree — gambarkan rantai kepemilikan dari UBO hingga entitas yang diaudit beserta persentase kepemilikan di tiap level (format teks atau deskripsi diagram)',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'     => 'subsidiaries_detail',
                        'label'    => 'Detail Entitas Anak dalam Struktur (nama, domisili, % kepemilikan langsung)',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                    [
                        'name'     => 'ownership_changes',
                        'label'    => 'Perubahan Struktur Kepemilikan pada Periode Ini (jika ada)',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                    [
                        'name'     => 'conclusion',
                        'label'    => 'Kesimpulan — konfirmasi apakah struktur kepemilikan sudah terdokumentasi dengan lengkap dan UBO sudah teridentifikasi',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                ],
            ],
        ]);

        // =====================================================
        // FORM 1410: PEMAHAMAN PERATURAN YANG RELEVAN & KONTROL LEGALITAS
        // Sumber: 1410a Pemahaman atas Peraturan yang Relevan PT IAS 2024.docx
        //         1410b Kontrol Legalitas PT IAS 2024.xlsx
        // Kolom: No | Nama Peraturan | Peraturan Terbaru | Dampak | Akun Terkait | Patuh/Tidak | Keterangan
        // =====================================================
        $seedForm('1410', [
            [
                'title'  => 'Pemahaman atas Peraturan yang Relevan',
                'fields' => [
                    [
                        'name'  => 'regulations_table',
                        'label' => 'Daftar Peraturan yang Relevan dengan Klien (format per baris: No | Nama & Nomor Peraturan | Peraturan Terbaru | Dampak Terhadap Entitas | Akun Utama Terkait | Patuh/Tidak Patuh | Keterangan/Bukti Kepatuhan)',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'     => 'conclusion_compliance',
                        'label'    => 'Kesimpulan Kepatuhan — ringkasan apakah entitas secara umum telah mematuhi peraturan yang relevan',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                ],
            ],
            [
                'title'  => 'Kontrol Legalitas (Peraturan OJK / Pasar Modal)',
                'fields' => [
                    [
                        'name'  => 'ojk_regulations_table',
                        'label' => 'Tabel Kontrol Legalitas Peraturan OJK (format: No POJK | Ketentuan | Kondisi Perusahaan | Peraturan Terkini | Status Kepatuhan)',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'     => 'directors_commissioners_compliance',
                        'label'    => 'Kepatuhan Susunan Direksi dan Dewan Komisaris (sesuai POJK No. 33/2014)',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                    [
                        'name'     => 'audit_committee_compliance',
                        'label'    => 'Kepatuhan Pembentukan Komite Audit (sesuai POJK No. 55/2015)',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                    [
                        'name'     => 'corporate_secretary_compliance',
                        'label'    => 'Kepatuhan Sekretaris Perusahaan (sesuai POJK No. 35/2014)',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                    [
                        'name'     => 'legalitas_conclusion',
                        'label'    => 'Kesimpulan Kontrol Legalitas',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                ],
            ],
        ]);

        // =====================================================
        // FORM 1420: PROSEDUR ANALITIK AWAL (PRELIMINARY ANALYTICAL REVIEW)
        // Sumber: 1420 Prosedur Analitik Awal_PT IAS 2024.xlsx
        // Sheet: PAR Sept 2024 Induk & PAR Sept 2024 Konsol
        // Kolom: Akun | Nilai 2024 (Rp) | Tahun Lalu 2023 (Rp) | Perubahan Rp | % | Komentar/Identifikasi Risiko
        // =====================================================
        $seedForm('1420', [
            [
                'title'  => 'Informasi Umum & Tujuan Prosedur',
                'fields' => [
                    [
                        'name'     => 'basis_laporan',
                        'label'    => 'Basis laporan yang digunakan (Induk / Konsolidasi / Keduanya) dan sumber data (Laporan Keuangan Interim / Trial Balance)',
                        'type'     => 'text',
                        'required' => false,
                    ],
                    [
                        'name'     => 'pm_threshold',
                        'label'    => 'Nilai Performance Materiality yang digunakan sebagai threshold perubahan signifikan',
                        'type'     => 'currency',
                        'required' => false,
                    ],
                    [
                        'name'     => 'significant_change_pct',
                        'label'    => 'Persentase perubahan yang dianggap signifikan (contoh: 20%)',
                        'type'     => 'text',
                        'required' => false,
                    ],
                ],
            ],
            [
                'title'  => 'Analisis Laporan Laba Rugi',
                'fields' => [
                    [
                        'name'  => 'par_penjualan',
                        'label' => 'Penjualan — Nilai 2024 (Rp) | Tahun Lalu (Rp) | Perubahan Rp | % | Komentar / Identifikasi Risiko',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'par_hpp',
                        'label' => 'Harga Pokok Penjualan — Nilai 2024 (Rp) | Tahun Lalu (Rp) | Perubahan Rp | % | Komentar / Identifikasi Risiko',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'par_beban_penjualan',
                        'label' => 'Beban Penjualan — Nilai 2024 (Rp) | Tahun Lalu (Rp) | Perubahan Rp | % | Komentar / Identifikasi Risiko',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'par_beban_umum',
                        'label' => 'Beban Umum dan Administrasi — Nilai 2024 (Rp) | Tahun Lalu (Rp) | Perubahan Rp | % | Komentar / Identifikasi Risiko',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'par_pendapatan_lain',
                        'label' => 'Pendapatan (Beban) Lain-lain — Nilai 2024 (Rp) | Tahun Lalu (Rp) | Perubahan Rp | % | Komentar / Identifikasi Risiko',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'par_beban_bunga',
                        'label' => 'Beban Bunga dan Keuangan — Nilai 2024 (Rp) | Tahun Lalu (Rp) | Perubahan Rp | % | Komentar / Identifikasi Risiko',
                        'type'  => 'textarea',
                    ],
                ],
            ],
            [
                'title'  => 'Analisis Neraca (Balance Sheet)',
                'fields' => [
                    [
                        'name'  => 'par_kas_bank',
                        'label' => 'Kas dan Setara Kas — Nilai 2024 (Rp) | Tahun Lalu (Rp) | Perubahan Rp | % | Komentar / Identifikasi Risiko',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'par_piutang_usaha_ketiga',
                        'label' => 'Piutang Usaha dari Pihak Ketiga — Nilai 2024 (Rp) | Tahun Lalu (Rp) | Perubahan Rp | % | Komentar',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'par_piutang_usaha_berelasi',
                        'label' => 'Piutang Usaha dari Pihak Berelasi — Nilai 2024 (Rp) | Tahun Lalu (Rp) | Perubahan Rp | % | Komentar',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'par_piutang_lain',
                        'label' => 'Piutang Lain-lain — Nilai 2024 (Rp) | Tahun Lalu (Rp) | Perubahan Rp | % | Komentar',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'par_persediaan',
                        'label' => 'Persediaan — Nilai 2024 (Rp) | Tahun Lalu (Rp) | Perubahan Rp | % | Komentar',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'     => 'par_aset_biologis',
                        'label'    => 'Aset Biologis — Nilai 2024 (Rp) | Tahun Lalu (Rp) | Perubahan Rp | % | Komentar',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                    [
                        'name'  => 'par_pajak_dimuka',
                        'label' => 'Pajak Dibayar Dimuka — Nilai 2024 (Rp) | Tahun Lalu (Rp) | Perubahan Rp | % | Komentar',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'par_biaya_dimuka',
                        'label' => 'Biaya Dibayar Dimuka & Uang Muka — Nilai 2024 (Rp) | Tahun Lalu (Rp) | Perubahan Rp | % | Komentar',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'par_aset_tetap',
                        'label' => 'Aset Tetap — Nilai 2024 (Rp) | Tahun Lalu (Rp) | Perubahan Rp | % | Komentar',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'     => 'par_aset_hak_guna',
                        'label'    => 'Aset Hak-Guna (PSAK 73) — Nilai 2024 (Rp) | Tahun Lalu (Rp) | Perubahan Rp | % | Komentar',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                    [
                        'name'  => 'par_utang_usaha',
                        'label' => 'Utang Usaha (Pihak Ketiga + Berelasi) — Nilai 2024 (Rp) | Tahun Lalu (Rp) | Perubahan Rp | % | Komentar',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'par_utang_bank',
                        'label' => 'Utang Bank — Nilai 2024 (Rp) | Tahun Lalu (Rp) | Perubahan Rp | % | Komentar',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'par_beban_akrual',
                        'label' => 'Beban Akrual — Nilai 2024 (Rp) | Tahun Lalu (Rp) | Perubahan Rp | % | Komentar',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'par_utang_pajak',
                        'label' => 'Utang Pajak — Nilai 2024 (Rp) | Tahun Lalu (Rp) | Perubahan Rp | % | Komentar',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'par_utang_aset_tetap',
                        'label' => 'Utang Pembelian Aset Tetap — Nilai 2024 (Rp) | Tahun Lalu (Rp) | Perubahan Rp | % | Komentar',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'par_modal',
                        'label' => 'Modal Saham & Tambahan Modal Disetor — Nilai 2024 (Rp) | Tahun Lalu (Rp) | Perubahan Rp | % | Komentar',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'     => 'par_akun_lain',
                        'label'    => 'Akun Lain yang Signifikan (di atas PM atau perubahan >20%) — uraikan per akun dengan format yang sama',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                ],
            ],
            [
                'title'  => 'Kesimpulan Prosedur Analitik Awal',
                'fields' => [
                    [
                        'name'  => 'par_conclusion',
                        'label' => 'Kesimpulan — Ringkasan area yang memerlukan perhatian khusus berdasarkan hasil prosedur analitik awal dan identifikasi risiko potensial',
                        'type'  => 'textarea',
                    ],
                ],
            ],
        ]);

        // =====================================================
        // FORM 1430: PROSES PELAPORAN KEUANGAN
        // Sumber: Revisi Form 1000 Inspeksi/1430 Proses Pelaporan Keuangan_PT IAS_2024.doc
        // Kolom: No | Audit Procedure | Y/N/NA | Comment and/or Ref
        // 3 Seksi: Penyusunan LK, Penyesuaian, Konsolidasi
        // =====================================================
        $seedForm('1430', [
            [
                'title'  => 'Penyusunan Laporan Keuangan dan Pengungkapan',
                'fields' => [
                    [
                        'name'  => 'q1_competent_personnel',
                        'label' => '1. Apakah personel yang bertanggung jawab atas persiapan laporan keuangan kompeten, terlatih, dan diawasi secara memadai? Bagaimana manajemen mengikuti kebijakan akuntansi dan pengungkapan terbaru?',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'     => 'q1_comment',
                        'label'    => 'Komentar / Ref No. 1',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                    [
                        'name'  => 'q2_preparation_process',
                        'label' => '2. Jelaskan proses penyusunan laporan keuangan dan pengungkapan (alur dari pengumpulan data sampai persetujuan)',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'     => 'q2_comment',
                        'label'    => 'Komentar / Ref No. 2 — jelaskan proses penyusunan LK secara rinci',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                    [
                        'name'  => 'q3_senior_review',
                        'label' => '3. Apakah ada tinjauan laporan keuangan oleh manajemen senior sebelum dirilis?',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'     => 'q3_comment',
                        'label'    => 'Komentar / Ref No. 3',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                    [
                        'name'  => 'q4_segment_reporting',
                        'label' => '4. Jika applicable, jelaskan proses untuk mengidentifikasi segmen yang dapat dilaporkan untuk pelaporan segmental',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'     => 'q4_comment',
                        'label'    => 'Komentar / Ref No. 4',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                    [
                        'name'  => 'q5_subsidiary_process',
                        'label' => '5. Jika applicable, jelaskan proses identifikasi dan akuntansi entitas anak serta penanganan laporan akhir tahun entitas anak yang berbeda dengan laporan akhir tahun grup',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'     => 'q5_comment',
                        'label'    => 'Komentar / Ref No. 5',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                ],
            ],
            [
                'title'  => 'Penyesuaian Pelaporan Keuangan',
                'fields' => [
                    [
                        'name'  => 'q6_adjustment_responsibility',
                        'label' => '6. Siapa yang bertanggung jawab atas penyesuaian laporan keuangan, dan siapa yang mengotorisasinya?',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'     => 'q6_comment',
                        'label'    => 'Komentar / Ref No. 6',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                    [
                        'name'  => 'q7_adjustment_basis',
                        'label' => '7. Apa dasar penyesuaian atas pelaporan keuangan yang biasanya dilakukan?',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'     => 'q7_comment',
                        'label'    => 'Komentar / Ref No. 7',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                    [
                        'name'  => 'q8_adjustment_completeness',
                        'label' => '8. Bagaimana klien memastikan bahwa semua penyesuaian yang diperlukan telah dihitung dan diproses ke dalam laporan keuangan?',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'     => 'q8_comment',
                        'label'    => 'Komentar / Ref No. 8',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                    [
                        'name'  => 'q9_intragroup_elimination',
                        'label' => '9. Bagaimana klien memastikan transaksi intra-grup dan keuntungan yang belum direalisasi serta saldo intra-grup telah direkonsiliasi dan dieliminasi jika diperlukan?',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'     => 'q9_comment',
                        'label'    => 'Komentar / Ref No. 9',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                    [
                        'name'  => 'q10_adjustment_accuracy',
                        'label' => '10. Bagaimana klien memastikan bahwa penyesuaian didukung oleh informasi yang akurat?',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'     => 'q10_comment',
                        'label'    => 'Komentar / Ref No. 10',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                    [
                        'name'  => 'q11_nonstandard_journal',
                        'label' => '11. Apakah ada entri jurnal non-standar yang diproses di luar kegiatan bisnis biasanya?',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'     => 'q11_comment',
                        'label'    => 'Komentar / Ref No. 11',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                    [
                        'name'  => 'q12_minority_arrangement',
                        'label' => '12. Jelaskan setiap pengaturan dengan pemilik mayoritas atau kepentingan minoritas mengenai kerugian yang ditimbulkan oleh entitas anak',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'     => 'q12_comment',
                        'label'    => 'Komentar / Ref No. 12',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                ],
            ],
            [
                'title'  => 'Konsolidasian',
                'fields' => [
                    [
                        'name'  => 'q13_accounting_policy_uniform',
                        'label' => '13. Jelaskan proses untuk memperoleh pemahaman tentang kebijakan akuntansi yang digunakan oleh cabang/entitas anak, termasuk apakah kebijakan akuntansi yang seragam digunakan',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'     => 'q13_comment',
                        'label'    => 'Komentar / Ref No. 13',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                    [
                        'name'  => 'q14_policy_reconciliation',
                        'label' => '14. Jelaskan apakah kebijakan akuntansi yang seragam digunakan, dan jika tidak, bagaimana direkonsiliasi dengan kantor pusat?',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'  => 'q15_complete_reporting',
                        'label' => '15. Jelaskan proses untuk memastikan pelaporan yang lengkap, akurat, dan tepat waktu oleh kantor cabang untuk dikombinasikan dalam laporan keuangan kantor pusat',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'  => 'q16_foreign_currency',
                        'label' => '16. Jelaskan proses penerjemahan informasi keuangan entitas asing ke dalam mata uang laporan keuangan grup',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'  => 'q17_experienced_staff',
                        'label' => '17. Apakah staf berpengalaman dalam penyusunan laporan keuangan konsolidasi?',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'  => 'q18_manual_or_system',
                        'label' => '18. Apakah laporan keuangan disiapkan secara manual atau menggunakan sistem perangkat lunak?',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'     => 'q18_comment',
                        'label'    => 'Keterangan sistem/software yang digunakan (jika pakai software)',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                    [
                        'name'  => 'q19_data_transfer',
                        'label' => '19. Jelaskan bagaimana informasi akuntansi ditransfer ke dalam kertas kerja laporan keuangan',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'  => 'q20_applicable_standards',
                        'label' => '20. Jelaskan bagaimana perusahaan memastikan laporan keuangan disiapkan menggunakan standar pelaporan keuangan yang berlaku dan relevan (PSAK, IFRS, dll.)',
                        'type'  => 'dropdown',
                    ],
                    [
                        'name'     => 'q20_comment',
                        'label'    => 'Komentar / Ref No. 20',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                ],
            ],
        ]);

        // =====================================================
        // FORM 1440: FRAUD RISK ASSESSMENT
        // Sumber: 1440 Fraud Risk Assessment PT IAS 2024.doc
        // A. Kecurangan laporan keuangan (3 faktor risiko + RMM Y/N + penjelasan)
        // B. Penyalahgunaan aset (2 faktor risiko + RMM Y/N + penjelasan)
        // C. Permintaan orang lain (diskusi wawancara)
        // =====================================================
        $seedForm('1440', [
            [
                'title'  => 'A. Salah Saji dari Kecurangan Laporan Keuangan',
                'fields' => [
                    [
                        'name'  => 'a1_management_incentive',
                        'label' => '1. Faktor yang mungkin menunjukkan Manajemen memiliki INSENTIF atau berada di bawah TEKANAN untuk terlibat dalam kecurangan pelaporan keuangan — RMM (Y/N)',
                        'type'  => 'dropdown',
                        'options' => json_encode([['value' => 'Y', 'label' => 'Y (Ada Risiko)'], ['value' => 'N', 'label' => 'N (Tidak Ada Risiko)']]),
                    ],
                    [
                        'name'  => 'a1_explanation',
                        'label' => 'Penjelasan / Keterangan No. A1',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'a2_management_opportunity',
                        'label' => '2. Faktor yang mungkin mengindikasikan Manajemen memiliki PELUANG untuk terlibat dalam kecurangan pelaporan keuangan (misal: tidak ada pemisahan tugas, tidak ada review berjenjang) — RMM (Y/N)',
                        'type'  => 'dropdown',
                        'options' => json_encode([['value' => 'Y', 'label' => 'Y (Ada Risiko)'], ['value' => 'N', 'label' => 'N (Tidak Ada Risiko)']]),
                    ],
                    [
                        'name'  => 'a2_explanation',
                        'label' => 'Penjelasan / Keterangan No. A2 (uraikan kontrol yang ada: pemisahan tugas, review berjenjang, sistem akuntansi yang digunakan)',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'a3_attitude_rationalization',
                        'label' => '3. Faktor yang mencerminkan SIKAP/RASIONALISASI anggota dewan, Manajemen, atau karyawan yang memungkinkan mereka terlibat dalam kecurangan pelaporan keuangan — RMM (Y/N)',
                        'type'  => 'dropdown',
                        'options' => json_encode([['value' => 'Y', 'label' => 'Y (Ada Risiko)'], ['value' => 'N', 'label' => 'N (Tidak Ada Risiko)']]),
                    ],
                    [
                        'name'  => 'a3_explanation',
                        'label' => 'Penjelasan / Keterangan No. A3',
                        'type'  => 'textarea',
                    ],
                ],
            ],
            [
                'title'  => 'B. Salah Saji dari Penyalahgunaan Aset',
                'fields' => [
                    [
                        'name'  => 'b1_asset_incentive',
                        'label' => '1. Faktor yang mungkin mengindikasikan Manajemen atau karyawan memiliki INSENTIF atau berada di bawah tekanan untuk MENYALAHGUNAKAN ASET — RMM (Y/N)',
                        'type'  => 'dropdown',
                        'options' => json_encode([['value' => 'Y', 'label' => 'Y (Ada Risiko)'], ['value' => 'N', 'label' => 'N (Tidak Ada Risiko)']]),
                    ],
                    [
                        'name'  => 'b1_explanation',
                        'label' => 'Penjelasan / Keterangan No. B1',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'b2_asset_rationalization',
                        'label' => '2. Faktor risiko yang mencerminkan SIKAP/RASIONALISASI Manajemen atau karyawan yang memungkinkan mereka terlibat dalam penyalahgunaan aset — RMM (Y/N)',
                        'type'  => 'dropdown',
                        'options' => json_encode([['value' => 'Y', 'label' => 'Y (Ada Risiko)'], ['value' => 'N', 'label' => 'N (Tidak Ada Risiko)']]),
                    ],
                    [
                        'name'  => 'b2_explanation',
                        'label' => 'Penjelasan / Keterangan No. B2 (uraikan kontrol pengamanan aset yang ada)',
                        'type'  => 'textarea',
                    ],
                ],
            ],
            [
                'title'  => 'C. Permintaan / Wawancara dengan Pihak Lain dalam Entitas',
                'fields' => [
                    [
                        'name'  => 'c_interviewees',
                        'label' => 'Daftar Orang yang Diwawancarai (format: No | Nama | Jabatan/Posisi)',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'c_discussion_notes',
                        'label' => 'Catatan Diskusi — Pandangan mereka tentang risiko kecurangan dan pengetahuan tentang kecurangan atau dugaan kecurangan yang mempengaruhi entitas',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'fraud_conclusion',
                        'label' => 'Kesimpulan Penilaian Risiko Kecurangan — tingkat risiko (Rendah / Sedang / Tinggi) dan ringkasan alasan',
                        'type'  => 'textarea',
                    ],
                ],
            ],
        ]);

        // =====================================================
        // FORM 1441: INTERVIEW KLIEN – PENILAIAN RISIKO KECURANGAN
        // Sumber: 1441 Penilaian Risiko Kecurangan - Interview Klien PT IAS 2024_Rev 1.doc
        // Interview dengan pihak yang bertanggung jawab atas tata kelola
        // =====================================================
        $seedForm('1441', [
            [
                'title'  => 'Daftar Pihak yang Diwawancarai',
                'fields' => [
                    [
                        'name'  => 'interviewees_list',
                        'label' => 'Daftar Nama dan Jabatan Pihak yang Diwawancarai (format: No | Nama | Jabatan/Title)',
                        'type'  => 'textarea',
                    ],
                ],
            ],
            [
                'title'  => 'Pertanyaan Interview Penilaian Risiko Kecurangan',
                'fields' => [
                    [
                        'name'  => 'qa_fraud_risk_assessment',
                        'label' => 'a) Jelaskan penilaian Anda tentang risiko kecurangan dalam Perusahaan. Apakah potensi kecurangannya tinggi, sedang, atau rendah? — Jawaban / Komentar',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'qa_actual_fraud_knowledge',
                        'label' => 'b) Apakah Anda memiliki pengetahuan aktual terkait perilaku kecurangan yang terjadi dalam Perusahaan? — Jawaban / Komentar',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'qa_oversight_involvement',
                        'label' => 'c) Jelaskan keterlibatan Anda dalam mengawasi risiko kecurangan dan program Anda dalam memitigasi risiko — Jawaban / Komentar',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'     => 'qa_additional',
                        'label'    => 'd) Pertanyaan tambahan atau informasi relevan lainnya yang diperoleh selama interview',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                ],
            ],
            [
                'title'  => 'Kesimpulan Interview',
                'fields' => [
                    [
                        'name'  => 'interview_conclusion',
                        'label' => 'Kesimpulan — Penilaian risiko kecurangan berdasarkan hasil interview (Rendah / Sedang / Tinggi) beserta alasan dan dasar penilaian',
                        'type'  => 'textarea',
                    ],
                ],
            ],
        ]);

        // =====================================================
        // FORM 1450: PENILAIAN RISIKO BISNIS
        // Sumber: 1450 Penilaian Risiko Bisnis PT IAS 2024- Rev 1.doc
        // Format: No | Faktor Risiko | RMM Y/N | Penjelasan
        // Bagian: A Industri, B Regulasi, C Operasi, D Produk/Pasar, E Fasilitas, F Pembiayaan, G Tujuan
        // =====================================================
        $seedForm('1450', [
            [
                'title'  => 'A. Kondisi-kondisi Industri',
                'fields' => [
                    [
                        'name'  => 'a1_1_industry_characteristics',
                        'label' => '1.1 Karakteristik unik dari industri — RMM (Y/N) | Penjelasan',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'a1_2_industry_cycle',
                        'label' => '1.2 Tahap siklus industri yang telah dicapai pasar — RMM (Y/N) | Penjelasan',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'a1_3_seasonal_impact',
                        'label' => '1.3 Dampak aktivitas siklus atau musiman — RMM (Y/N) | Penjelasan',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'a1_4_technology_product',
                        'label' => '1.4 Potensi dampak perubahan teknologi pada produk — RMM (Y/N) | Penjelasan',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'a1_5_technology_production',
                        'label' => '1.5 Potensi dampak perubahan teknologi terhadap metode produksi — RMM (Y/N) | Penjelasan',
                        'type'  => 'textarea',
                    ],
                ],
            ],
            [
                'title'  => 'B. Peraturan Lingkungan',
                'fields' => [
                    [
                        'name'  => 'b2_1_regulations',
                        'label' => '2.1 Undang-undang, peraturan, dan kerangka peraturan yang secara signifikan mempengaruhi operasi entitas — RMM (Y/N) | Penjelasan',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'b2_2_taxation',
                        'label' => '2.2 Perpajakan — RMM (Y/N) | Penjelasan',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'b2_3_environmental',
                        'label' => '2.3 Persyaratan Lingkungan Hidup — RMM (Y/N) | Penjelasan',
                        'type'  => 'textarea',
                    ],
                ],
            ],
            [
                'title'  => 'C. Operasi Bisnis',
                'fields' => [
                    [
                        'name'  => 'c3_1_business_operations',
                        'label' => '3.1 Operasi Bisnis (sifat, produk, segmen pelanggan utama) — RMM (Y/N) | Penjelasan',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'c3_2_revenue_nature',
                        'label' => '3.2 Sifat sumber pendapatan (Pabrikan / Pedagang / Importir / Eksportir / dll.) — RMM (Y/N) | Penjelasan',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'c3_3_group_structure',
                        'label' => '3.3 Laporan keuangan bisnis adalah kelompok dan mengandung entitas anak — RMM (Y/N) | Penjelasan',
                        'type'  => 'textarea',
                    ],
                ],
            ],
            [
                'title'  => 'D. Produk atau Layanan dan Pasar',
                'fields' => [
                    [
                        'name'  => 'd_products_markets',
                        'label' => 'Faktor-faktor Produk & Pasar (4.1–4.17) — dokumentasikan dalam satu uraian: sifat permintaan, syarat pembayaran, margin laba, area geografis, sifat dan penggunaan produk, persaingan, kebijakan harga, reputasi, jaminan, metode penjualan, jaringan distribusi, proses manufaktur, pelaksanaan operasi, pemasok penting, R&D, transaksi pihak berelasi. Format per poin: No | Faktor | RMM (Y/N) | Penjelasan',
                        'type'  => 'textarea',
                    ],
                ],
            ],
            [
                'title'  => 'E. Fasilitas Pabrik dan Peralatan',
                'fields' => [
                    [
                        'name'  => 'e5_1_facilities',
                        'label' => '5.1 Kondisi, usia, tingkat keusangan fasilitas dan peralatan — RMM (Y/N) | Penjelasan',
                        'type'  => 'textarea',
                    ],
                ],
            ],
            [
                'title'  => 'F. Pembiayaan',
                'fields' => [
                    [
                        'name'  => 'f6_1_debt_structure',
                        'label' => '6.1 Struktur utang, termasuk perjanjian, pembatasan, jaminan, dan pengaturan pembiayaan di luar neraca — RMM (Y/N) | Penjelasan',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'f6_2_leasing',
                        'label' => '6.2 Penyewaan properti, pabrik, atau peralatan untuk digunakan dalam bisnis — RMM (Y/N) | Penjelasan',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'f6_3_beneficial_owner',
                        'label' => '6.3 Pemilik yang menguntungkan / Beneficial Owner (lokal/asing, reputasi dan pengalaman bisnis) — RMM (Y/N) | Penjelasan',
                        'type'  => 'textarea',
                    ],
                ],
            ],
            [
                'title'  => 'G. Tujuan dan Strategi',
                'fields' => [
                    [
                        'name'  => 'g7_1_objectives',
                        'label' => '7.1 Adanya tujuan dan strategi bisnis — RMM (Y/N) | Penjelasan',
                        'type'  => 'textarea',
                    ],
                    [
                        'name'  => 'business_risk_conclusion',
                        'label' => 'Kesimpulan Penilaian Risiko Bisnis — ringkasan risiko bisnis yang teridentifikasi dan dampaknya terhadap risiko salah saji material (RMM)',
                        'type'  => 'textarea',
                    ],
                ],
            ],
        ]);

        // =====================================================
        // FORM 1610: MATERIALITY SAMPLING
        // Sumber: 1610 Materiality-Sampling 24_PT IAS Konsol Rev 1.xlsx
        // Isi: Tabel materialitas sliding scale (Total Assets/Revenue, Net Assets, Income Before Tax)
        //      + perhitungan Overall Materiality, Performance Materiality, MUD
        // =====================================================
        $seedForm('1610', [
            [
                'title'  => 'Basis dan Parameter Perhitungan Materialitas',
                'fields' => [
                    [
                        'name'  => 'closing_date',
                        'label' => 'Tanggal Penutupan / Closing Date (contoh: 31 Desember 2024)',
                        'type'  => 'date',
                    ],
                    [
                        'name'  => 'materiality_basis',
                        'label' => 'Basis Materialitas yang Dipilih (Total Assets / Revenue / Net Assets / Income Before Tax)',
                        'type'  => 'dropdown',
                        'options' => json_encode([
                            ['value' => 'income_before_tax', 'label' => 'Income Before Tax (Laba Sebelum Pajak)'],
                            ['value' => 'total_assets', 'label' => 'Total Assets (Total Aset)'],
                            ['value' => 'revenue', 'label' => 'Revenue (Pendapatan)'],
                            ['value' => 'net_assets', 'label' => 'Net Assets / Equity (Aset Bersih/Ekuitas)'],
                        ]),
                    ],
                    [
                        'name'     => 'basis_justification',
                        'label'    => 'Alasan/Justifikasi Pemilihan Basis Materialitas',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                    [
                        'name'  => 'basis_value_idr',
                        'label' => 'Nilai Basis yang Digunakan (Rp) — nilai Income Before Tax / Total Assets / Revenue / Equity yang menjadi dasar perhitungan',
                        'type'  => 'currency',
                    ],
                    [
                        'name'     => 'exchange_rate',
                        'label'    => 'Kurs yang Digunakan (Rp/USD) — jika konversi ke USD diperlukan',
                        'type'     => 'number',
                        'required' => false,
                    ],
                    [
                        'name'     => 'basis_value_usd',
                        'label'    => 'Nilai Basis dalam USD (hasil konversi)',
                        'type'     => 'number',
                        'required' => false,
                    ],
                ],
            ],
            [
                'title'  => 'Tabel Sliding Scale & Perhitungan Materialitas',
                'fields' => [
                    [
                        'name'     => 'sliding_scale_table',
                        'label'    => 'Tabel Sliding Scale yang Digunakan (format: Range USD | % Total Assets or Revenue | % Net Assets | % Income Before Tax)',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                    [
                        'name'  => 'applicable_percentage',
                        'label' => 'Persentase yang Diterapkan berdasarkan Sliding Scale (contoh: 10% untuk Income Before Tax dalam range pertama)',
                        'type'  => 'text',
                    ],
                    [
                        'name'  => 'overall_materiality_usd',
                        'label' => 'Overall Materiality (USD)',
                        'type'  => 'number',
                    ],
                    [
                        'name'  => 'overall_materiality_idr',
                        'label' => 'Overall Materiality (IDR / Rp)',
                        'type'  => 'currency',
                    ],
                    [
                        'name'  => 'client_risk_classification',
                        'label' => 'Klasifikasi Risiko Klien (High Risk / Non High Risk) — menentukan persentase Performance Materiality',
                        'type'  => 'dropdown',
                        'options' => json_encode([
                            ['value' => 'non_high_risk', 'label' => 'Non High Risk (PM = 80% dari OM)'],
                            ['value' => 'high_risk', 'label' => 'High Risk (PM < 80% dari OM)'],
                        ]),
                    ],
                    [
                        'name'  => 'performance_materiality_pct',
                        'label' => 'Persentase Performance Materiality dari Overall Materiality (contoh: 80%)',
                        'type'  => 'text',
                    ],
                    [
                        'name'  => 'performance_materiality_usd',
                        'label' => 'Performance Materiality (USD)',
                        'type'  => 'number',
                    ],
                    [
                        'name'  => 'performance_materiality_idr',
                        'label' => 'Performance Materiality (IDR / Rp)',
                        'type'  => 'currency',
                    ],
                ],
            ],
            [
                'title'  => 'Tolerable Misstatement & MUD (Minimum of Unadjusted Differences)',
                'fields' => [
                    [
                        'name'  => 'tolerable_misstatement_idr',
                        'label' => 'Tolerable Misstatement / Batas Kesalahan yang Dapat Ditoleransi — Agregat (IDR) — biasanya = Overall Materiality x 20% atau nilai tertentu',
                        'type'  => 'currency',
                    ],
                    [
                        'name'  => 'mud_pct',
                        'label' => 'Persentase MUD dari Overall Materiality (contoh: 5%)',
                        'type'  => 'text',
                    ],
                    [
                        'name'  => 'mud_usd',
                        'label' => 'MUD — Minimum of Unadjusted Differences (USD)',
                        'type'  => 'number',
                    ],
                    [
                        'name'  => 'mud_idr',
                        'label' => 'MUD — Minimum of Unadjusted Differences (IDR) — batas minimum kesalahan yang tidak dikoreksi yang perlu dicatat di MUD',
                        'type'  => 'currency',
                    ],
                    [
                        'name'     => 'materiality_notes',
                        'label'    => 'Catatan Materialitas — penjelasan tambahan atau justifikasi keputusan materialitas',
                        'type'     => 'textarea',
                        'required' => false,
                    ],
                ],
            ],
        ]);

        $this->command?->info('Fase1000SisaFieldsSeeder selesai: 1120, 1130A, 1130B, 1130C, 1130D, 1410, 1420, 1430, 1440, 1441, 1450, 1610 berhasil di-seed.');
    }
}
