<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder untuk mengisi data jawaban (answers) awal untuk seluruh form Fase 1000
 * pada perikatan PT Indo American Seafoods Tbk (IAS-2024), bersumber dari
 * dokumen-dokumen riil di folder "PT Indo American Seafoods Tbk/1000 Risk Assesment".
 */
class Fase1000AnswersSeeder extends Seeder
{
    public function run(): void
    {
        $client = DB::table('clients')->where('name', 'like', '%Indo American%')->first();
        if (! $client) {
            $this->command?->warn('Client PT Indo American Seafoods Tbk tidak ditemukan.');
            return;
        }

        $engagement = DB::table('engagements')->where('client_id', $client->id)->first();
        if (! $engagement) {
            $this->command?->warn('Engagement untuk IAS tidak ditemukan.');
            return;
        }

        $user = DB::table('users')->where('email', 'junior@kapmgn.test')->first()
            ?? DB::table('users')->first();

        $now = now();

        /**
         * Helper untuk membuat/mengambil response form dan menyimpan daftar jawaban
         */
        $seedAnswers = function (string $formCode, array $fieldValues, string $status = 'draft') use ($engagement, $user, $now): void {
            $form = DB::table('audit_forms')->where('code', $formCode)->first();
            if (! $form) {
                return;
            }

            // Dapatkan atau buat response
            $response = DB::table('audit_form_responses')
                ->where('form_id', $form->id)
                ->where('engagement_id', $engagement->id)
                ->first();

            if (! $response) {
                $responseId = DB::table('audit_form_responses')->insertGetId([
                    'form_id'       => $form->id,
                    'engagement_id' => $engagement->id,
                    'user_id'       => $user->id,
                    'status'        => $status,
                    'submitted_at'  => $status !== 'draft' ? $now : null,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            } else {
                $responseId = $response->id;
            }

            // Ambil mapping field_name => field_id
            $sectionIds = DB::table('audit_form_sections')->where('form_id', $form->id)->pluck('id');
            $fields = DB::table('audit_form_fields')->whereIn('section_id', $sectionIds)->get()->keyBy('field_name');

            foreach ($fieldValues as $fieldName => $value) {
                $field = $fields->get($fieldName);
                if (! $field) {
                    continue;
                }

                DB::table('audit_form_answers')->updateOrInsert(
                    ['response_id' => $responseId, 'field_id' => $field->id],
                    ['response_value' => $value, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        };

        // =====================================================
        // 1100: MEMO PENERIMAAN & KEBERLANJUTAN KLIEN
        // =====================================================
        $seedAnswers('1100', [
            'q1_jawaban' => 'Y', 'q1_komentar' => 'BOD dan BOC memiliki reputasi bisnis yang baik dan tidak pernah terlibat kasus pidana/perdata.',
            'q2_jawaban' => 'T', 'q2_komentar' => 'Klien tidak menunjukkan sikap agresif atau batasan ruang lingkup pada audit sebelumnya.',
            'q3_jawaban' => 'T', 'q3_komentar' => 'Tidak terdapat transaksi istimewa atau pihak berelasi yang melanggar ketentuan hukum.',
            'q4_jawaban' => 'Y', 'q4_komentar' => 'KAP memiliki personel dengan kompetensi industri perikanan dan waktu yang memadai.',
            'q5_jawaban' => 'Y', 'q5_komentar' => 'Telah menelaah LK auditan 2 tahun terakhir (2022-2023) dengan opini WTP.',
            'q6_jawaban' => 'Y', 'q6_komentar' => 'Kertas kerja tahun 2023 diaudit oleh KAP MGN sendiri sehingga akses penuh tersedia.',
            'q7_jawaban' => 'Y', 'q7_komentar' => 'Kebijakan akuntansi diterapkan konsisten sesuai SAK yang berlaku.',
            'q8_jawaban' => 'T', 'q8_komentar' => 'Tidak ada pembatasan saldo awal, opini wajar tanpa modifikasian.',
            'q9_jawaban' => 'T', 'q9_komentar' => 'Tidak terdapat penolakan akses kertas kerja oleh auditor pendahulu (karena KAP yang sama).',
            'q10_jawaban' => 'T', 'q10_komentar' => 'Tidak ada keraguan atas integritas manajemen berdasarkan survey dan background check.',
            'q11_jawaban' => 'Y', 'q11_komentar' => 'Telah dipastikan tidak ada ancaman independensi sesuai Seksi 290 Kode Etik.',
            'q12_jawaban' => 'T', 'q12_komentar' => 'Tidak ada benturan kepentingan dengan perikatan atau klien lain.',
            'q13_jawaban' => 'Y', 'q13_komentar' => 'Kompetensi tim telah diverifikasi memenuhi standar ISA dan SPM-1.',
            'q14_jawaban' => 'Y', 'q14_komentar' => 'Alokasi waktu dan sumber daya cukup untuk menyelesaikan audit tepat waktu.',
            'q15_jawaban' => 'T', 'q15_komentar' => 'Klien tidak mengalami kesulitan likuiditas atau indikasi kebangkrutan.',
            'q16_jawaban' => 'Y', 'q16_komentar' => 'Pengendalian internal entitas telah dievaluasi dan berfungsi memadai.',
            'q17_jawaban' => 'T', 'q17_komentar' => 'Tidak ada litigasi material atau tuntutan hukum yang sedang berjalan.',
            'q18_jawaban' => 'Y', 'q18_komentar' => 'Struktur permodalan dan UBO telah diverifikasi dengan akta pendirian dan perubahan.',
            'q19_jawaban' => 'Y', 'q19_komentar' => 'Honorarium audit wajar dan mencerminkan kompleksitas pekerjaan.',
            'q20_jawaban' => 'T', 'q20_komentar' => 'Tidak ada ketergantungan fee yang signifikan dari klien ini (< 15% total fee KAP).',
            'q21_jawaban' => 'Y', 'q21_komentar' => 'Surat perikatan audit telah disusun dan disetujui kedua belah pihak.',
            'q22_jawaban' => 'Y', 'q22_komentar' => 'Komunikasi dengan TCWG dan Komite Audit telah berjalan sesuai standar.',
            'q23_jawaban' => 'Y', 'q23_komentar' => 'Dokumentasi penerimaan klien telah lengkap dan direview oleh Partner.',
            'q24_jawaban' => 'Y', 'q24_komentar' => 'Kesimpulan: Perikatan audit PT Indo American Seafoods Tbk diterima untuk tahun buku 2024.',
        ]);

        // =====================================================
        // 1110: SURVEY KLIEN
        // Sumber: 1110 Survey Klien IAS 2024 Rev 1.docx
        // Nilai verbatim dari dokumen Word asli:
        // - Name of Shareholders: HANYA ada Nama & % Kepemilikan (tidak ada jumlah lembar / nilai)
        // =====================================================
        $seedAnswers('1110', [
            'date_of_survey'              => '2024-10-18',
            'venue'                       => 'PT Indo American Seafoods Tbk dan Entitas Anak',
            'survey_time'                  => '09:00 - 12:00',
            'attendants'                  => json_encode([
                ['nama' => 'Saimi Saleh', 'jabatan' => 'Komisaris (UOB "Ultimate Beneficial Owner")'],
                ['nama' => 'Ibnu Syena Alfitra', 'jabatan' => 'Direktur Utama PT Indo American Seafoods'],
                ['nama' => 'Maurice Ganda', 'jabatan' => 'Partner Incharge KAP MGN & Rekan'],
                ['nama' => 'Kenny Nurardi Wijaya', 'jabatan' => 'Manager KAP MGN & Rekan'],
                ['nama' => 'Netty Lidya Simanjuntak', 'jabatan' => 'Senior KAP MGN & Rekan'],
                ['nama' => 'Raynaldi Tribuana', 'jabatan' => 'Senior KAP MGN & Rekan'],
                ['nama' => 'Daniel Gerald H', 'jabatan' => 'Junior KAP MGN & Rekan'],
                ['nama' => 'Aryn Sasikirana', 'jabatan' => 'Junior KAP MGN & Rekan'],
                ['nama' => 'Kurotun Ainiyah', 'jabatan' => 'Junior KAP MGN & Rekan'],
                ['nama' => 'Irene Rizka Amalia', 'jabatan' => 'Junior KAP MGN & Rekan'],
            ]),
            'legal_name'                  => 'PT Indo American Seafoods Tbk dan Entitas Anak',
            'scope_of_engagement'         => 'General Audit 31 Desember 2024',
            'financial_accounting_standard'=> 'Indonesian Financial Accounting Standard (Standar Akuntansi Keuangan di Indonesia)',
            'deliverables'                => 'Laporan Auditor Independen',
            'objectives'                  => 'Perusahaan menunjuk KAP MGN dan Rekan untuk melakukan audit atas laporan keuangan untuk tahun yang berakhir 31 Desember 2024 yang akan digunakan untuk pelaporan laporan keuangan tahunan serta kepada investor',
            'name_of_shareholders'        => json_encode([
                ['nama' => 'PT Indo American Foods Tbk dan Entitas Anak', 'persentase' => '69,24%'],
                ['nama' => 'Saimi Saleh', 'persentase' => '5,94%'],
                ['nama' => 'Ibnu Syena Alfitra', 'persentase' => '3,96%'],
                ['nama' => 'Masyarakat', 'persentase' => '20,86%'],
            ]),
            'name_of_management'          => json_encode([
                ['jabatan' => 'Komisaris', 'nama' => 'Saimi Saleh'],
                ['jabatan' => 'Komisaris Independen', 'nama' => 'Leo Herlambang'],
                ['jabatan' => 'Direktur Utama', 'nama' => 'Ibnu Syena Alfitra'],
                ['jabatan' => 'Direktur', 'nama' => 'Ibnu Surya Ramadhan'],
                ['jabatan' => 'Direktur', 'nama' => 'Abu Yazid'],
            ]),
            'ultimate_shareholder'        => 'Pengendali terakhir PT Indo American Foods Tbk dan Entitas Anak adalah Saimi Saleh.',
            'business_activity'           => "Kegiatan utama Perusahaan adalah di bidang makanan yaitu pengolahan udang.\nPerusahaan ini berdiri sejak tahun 2006.",
            'reporting_currency'          => 'IDR',
            'total_assets_period'         => 'September 30, 2024',
            'total_assets'                => json_encode([
                ['kategori' => 'Current Assets', 'nilai' => 330321157819],
                ['kategori' => 'Non-Current Assets', 'nilai' => 77792508860],
            ]),
            'total_revenues'              => 'Rp 160.540.499.628',
            'main_customers'              => json_encode([
                ['nama' => 'Censea Inc'],
                ['nama' => 'Eastern Fish Company LLC'],
                ['nama' => 'San Sugar Co, , Ltd'],
                ['nama' => 'Choice Canning Company Inc,'],
                ['nama' => 'Grahamakmur Ciptapratama'],
            ]),
            'main_distributors_vendors'   => json_encode([
                ['nama' => 'PT Indonesia Makan Udang'],
                ['nama' => 'CV Putra Widjaja Marine'],
            ]),
            'accounting_system'           => 'Tally',
            'accounting_issues'           => json_encode([
                [
                    'topik' => 'Realisasi Penggunaan Dana Penawaran Umum Perdana Saham',
                    'uraian' => "Perseroan melaksanakan Penawaran Umum Perdana Saham (IPO) pada tahun 2024. Berdasarkan POJK No. 30 tahun 2015 menerangkan bahwa Emoten wajib melaporkan realisasi penggunaan dana IPO dan segala hal yang berkaitan dengan penggunaan dana tersebut."
                ],
                [
                    'topik' => 'Piutang Usaha',
                    'uraian' => "Piutang Usaha Perseroan pada 30 September 2024 mengalami peningkatan sebesar Rp 18.004.797.977 atau setara 89,17% jika dibandingkan dengan 31 Desember 2023."
                ],
                [
                    'topik' => 'Persediaan',
                    'uraian' => "Persediaan Perseroan pada 30 September 2024 mengalami peningkatan sebesar Rp 10.765.630.157 atau setara 7,19% jika dibandingkan dengan 31 Desember 2023. Berdasarkan PSAK 202 \"Persediaan\" Par.09 diukur pada mana yang lebih rendah antara biaya perolehan dan nilai realisasi neto."
                ],
                [
                    'topik' => 'Utang Bank',
                    'uraian' => "Pada 30 September 2024, Grup mencatat utang bank jangka pendek sebesar Rp 197.771.078.247 atau setara dengan 86,84% dari jumlah liabilitas konsolidasian."
                ],
                [
                    'topik' => 'Aset Biologis',
                    'uraian' => "Grup memiliki aset biologis yang terdiri dari udang, pakan, dan benih udang."
                ]
            ]),
            'ethics'                      => 'Pemegang Saham dan Manajemen menunjuk KAP sebagai auditor Independen agar laporan keuangan perusahaan disajikan sesuai dengan standar akuntansi keuangan di Indonesia.',
            'conclusion'                  => 'Managing partner KAP MGN & Rekan ingin melanjutkan survey ini kepada PMPJ dan first pass background checking karena potensi yang besar di masa depan menjadi klien strategis bagi KAP MGN & Rekan.',
        ]);

        // =====================================================
        // 1120: SURAT KEBERATAN PROFESIONAL
        // =====================================================
        $seedAnswers('1120', [
            'prior_kap_type'       => 'KAP_MGN',
            'prior_kap_name'       => 'KAP Maurice Ganda Nainggolan dan Rekan (AP: Maurice Ganda Nainggolan)',
            'prior_audit_period'   => '31 Desember 2023',
            'has_objection_letter' => 'N',
            'objection_letter_file'=> null,
            'objection_statement'  => 'Surat Keberatan Profesional untuk audit 31 Desember 2024 tidak ada dikarenakan tahun 31 Desember 2023 diaudit oleh KAP Maurice Ganda Nainggolan dan Rekan dengan Maurice Ganda Nainggolan sebagai AP.',
        ]);

        // =====================================================
        // 1130A: INDEPENDENCE CHECKLIST
        // =====================================================
        // 1130A answers — sesuai DOC asli: hanya Yes/No/NA per pertanyaan
        $seedAnswers('1130A', [
            'q1_independence_check'            => 'N',
            'q1_threats_uncovered'             => 'N',
            'q1_threats_resolved'              => 'NA',
            'q2_audit_committee_preapproval'   => 'Y',
            'q3_consultation'                  => 'N',
            'q4_partner_rotation'              => 'N',
            'q4_cooling_period'                => 'NA',
            'q4_eqar_rotation'                 => 'N',
            'q4_eqar_cooling'                  => 'NA',
            'pep_status'                       => 'Y',
            'hrc_status'                       => 'N',
            'hrb_status'                       => 'N',
            'countries_status'                 => 'N',
        ]);

        // =====================================================
        // 1130B: FIRST PASS DATA
        // =====================================================
        $seedAnswers('1130B', [
            'company_name'              => 'PT INDO AMERICAN SEAFOODS Tbk dan Entitas Anak',
            'company_address'           => 'Jl. Ir. Sutami Km. 13 Desa Sukanegara, Kec. Tanjung Bintang, Kab. Lampung Selatan',
            'industry'                  => 'Bidang pengolahan udang',
            'incorporated_in'           => 'Lampung Selatan',
            'date_of_incorporation'     => '2006-04-06',
            'stockholders_about'                 => 'PT Indo American Seafoods Tbk merupakan perseroan terbatas terbuka yang bergerak di bidang perindustrian dan perdagangan hasil perikanan, terutama pengolahan dan pembekuan udang (cold storage & processing) yang didirikan di Lampung Selatan.',
            'stockholders_parent_background'     => 'PT Indo American Seafoods ("Perusahaan") didirikan berdasarkan Akta Notaris No. 5 tanggal 6 April 2006 dari Akhmadi Dachlan, S.H., Notaris di Bandar Lampung. Akta pendirian ini disahkan oleh Menteri Hukum dan Hak Asasi Manusia Republik Indonesia dalam Surat Keputusannya No. C-16465HT.01.01.TH.2006 tanggal 6 Juni 2006. Anggaran Dasar Perusahaan telah mengalami beberapa kali perubahan, terakhir sebagaimana dinyatakan dalam Akta Notaris No. 11 tanggal 20 Mei 2022 dari Akhmadi Dachlan, S.H., M.Kn., mengenai perubahan pasal 3 Anggaran Dasar maksud dan tujuan kegiatan usaha Perusahaan. Akta perubahan ini telah disahkan oleh Menteri Hukum dan Hak Asasi Manusia Republik Indonesia berdasarkan Surat Keputusan No. AHU-0095588.AH.01.11.Tahun 2022 tanggal 24 Mei 2022.',
            'stockholders_parent'                => json_encode([
                ['nama' => 'PT Indo American Food', 'jumlah_lembar' => 962500000],
                ['nama' => 'Saimi Saleh', 'jumlah_lembar' => 82500000],
                ['nama' => 'Ibnu Syena Alfitra', 'jumlah_lembar' => 55000000],
                ['nama' => 'Masyarakat (masing-masing dibawah 5%)', 'jumlah_lembar' => 290000000],
            ]),
            'stockholders_subsidiary_background' => 'PT Indokom Samudra Persada ("ISP") adalah suatu perseroan yang didirikan berdasarkan hukum Republik Indonesia, yang didirikan dengan nama PT Indokom Samudera Persada sesuai dengan Akta Pendirian ISP No. 9, tanggal 16 Agustus 2000, dibuat di hadapan Imran Ma\'aruf S.H., Notaris di Kota Bandar Lampung, dan telah mendapatkan pengesahan Menkumham berdasarkan Surat Keputusan No. C-1490 HT.01.01.TH 2001 Tahun 2001 tertanggal 28 Februari 2001, yang telah diumumkan dalam TBNRI No. 5902 pada BNRI No. 74, tanggal 14 September 2001 serta telah didaftarkan dalam Daftar Perseroan No. 303/BH.07.01/VII/2001 tertanggal 17 Juli 2001. Berdasarkan Akta No. 149/2023, struktur permodalan dan susunan pemegang saham ISP adalah sebagai berikut.',
            'stockholders_subsidiary'            => json_encode([
                ['nama' => 'Perseroan', 'jumlah_saham' => 1050],
                ['nama' => 'Saimi Saleh', 'jumlah_saham' => 600],
                ['nama' => 'Ibnu Syena Alfitra', 'jumlah_saham' => 400],
            ]),
            'directors_commissioners'   => json_encode([
                ['nama' => 'Ibnu Syena Alfitra', 'gender' => 'Man', 'no_identity' => '3578260805890003', 'address' => 'Laguna Barat 7 Blok A-7/3-5, Kota Surabaya', 'date_of_birth' => '1989-05-08', 'nationality' => 'Indonesia', 'function' => 'President Director'],
                ['nama' => 'Ibnu Surya Ramadhan', 'gender' => 'Man', 'no_identity' => '3578262502930001', 'address' => 'Laguna Barat 7 Blok A-7/3-5, Kota Surabaya', 'date_of_birth' => '1993-02-25', 'nationality' => 'Indonesia', 'function' => 'Director'],
                ['nama' => 'Abu Yazid', 'gender' => 'Man', 'no_identity' => '1871011612730002', 'address' => 'Jalan Galunggung Raya F.19, Kota Bandar Lampung', 'date_of_birth' => '1973-12-16', 'nationality' => 'Indonesia', 'function' => 'Director'],
                ['nama' => 'Saimi Saleh', 'gender' => 'Man', 'no_identity' => '3578260612580001', 'address' => 'Laguna Barat 7 Blok A-7/3-5, Kota Surabaya', 'date_of_birth' => '1958-12-06', 'nationality' => 'Indonesia', 'function' => 'President Commissioner'],
                ['nama' => 'Leo Herlambang', 'gender' => 'Man', 'no_identity' => '3578012802690001', 'address' => 'Florence J-9 No 9 Pakuwon City, Kota Surabaya', 'date_of_birth' => '1969-02-28', 'nationality' => 'Indonesia', 'function' => 'Independent Commissioner'],
            ]),
            'subsidiaries'              => json_encode([
                ['nama_entitas' => 'PT Indokom Samudera Persada', 'domisili' => 'Bandar Lampung'],
            ]),
            'investments'               => json_encode([
                ['nama_entitas' => 'PT Indokom Samudera Persada', 'kegiatan_usaha' => 'Pembesaran Crustacea Air Payau, Industri Pembekuan Biota Air Lainnya, Industri Makanan dan Masakan Olahan. Perdagangan Besar Hasil Perikanan.', 'tahun_pendirian' => '2020', 'tahun_penyertaan' => '2023', 'domisili' => 'Bandar Lampung', 'jumlah_aset' => '248490881601', 'persentase' => '51.22%'],
            ]),
        ]);

        // =====================================================
        // 1130C: BACKGROUND CHECK
        // Sumber verbatim: 1130 C. Background Check_PT IAS Tbk dan Entitas Anak_2024 Rev 1.docx
        // Results = repeater per-nama (nama, no_identitas/ref, penjelasan)
        // =====================================================
        $seedAnswers('1130C', [
            'searched_names'         => json_encode([
                ['nama' => 'H Saimi Saleh S.E.,M.M'],
                ['nama' => 'Hendra, S.E., CPA'],
                ['nama' => 'Dr. Leo Herlambang'],
                ['nama' => 'Ibnu Syena Alfitra S.T.,M.I.B'],
                ['nama' => 'Ibnu Surya Ramadhan'],
                ['nama' => 'Abu Yazid'],
                ['nama' => 'PT Indo American Seafoods Tbk dan Entitas Anak'],
            ]),
            'search_results'         => json_encode([
                ['nama' => 'H Saimi Saleh S.E.,M.M', 'no_identitas' => '4572005334000', 'penjelasan' => 'Tidak ditemukan catatan negatif hukum / litigasi.'],
                ['nama' => 'Hendra, S.E., CPA', 'no_identitas' => '', 'penjelasan' => 'Tidak ditemukan catatan negatif hukum / litigasi.'],
                ['nama' => 'Dr. Leo Herlambang', 'no_identitas' => 'right635000', 'penjelasan' => 'Tidak ditemukan catatan negatif hukum / litigasi.'],
                ['nama' => 'Ibnu Syena Alfitra S.T.,M.I.B', 'no_identitas' => '457200317500', 'penjelasan' => 'Tidak ditemukan catatan negatif hukum / litigasi.'],
                ['nama' => 'Ibnu Surya Ramadhan', 'no_identitas' => 'right1460500', 'penjelasan' => 'Tidak ditemukan catatan negatif hukum / litigasi.'],
                ['nama' => 'Abu Yazid', 'no_identitas' => '', 'penjelasan' => 'Tidak ditemukan catatan negatif hukum / litigasi.'],
                ['nama' => 'PT Indo American Seafoods Tbk dan Entitas Anak', 'no_identitas' => '', 'penjelasan' => 'Tidak ditemukan sanksi administratif / pencabutan izin.'],
            ]),
            'ias_company_background' => 'PT Indo American Seafoods Tbk ("Perusahaan") didirikan berdasarkan Akta Notaris No. 5 tanggal 6 April 2006 dari Akhmad Dachlan, S.H., notaris di Bandar Lampung Akta pendirian ini telah disahkan oleh Menteri Hukum dan Hak Asasi Manusia Republik Indonesia dengan Surat Keputusannya No. C-16465HT.01.01.TH.2006 tanggal 6 Juni 2006 dan telah diumumkan dalam Lembar Berita Negara Republik Indonesia No. AHU-0011137.AH.01.09. Tahun 2010 tanggal 12 Februari 2010.',
            'ias_business_purpose'   => 'Perusahaan terutama bergerak dalam bidang Perindustrian, Perdagangan, Industri pembekuan biota air lainnya, Industri makanan dan masakan olahan, perdagangan besar hasil perikanan. Pada saat ini Perusahaan bergerak dalam bidang pengolahan udang.',
            'isp_company_background' => 'ISP adalah suatu perseroan yang didirikan berdasarkan hukum Republik Indonesia, yang didirikan dengan nama PT Indokom Samudera Persada sesuai dengan Akta Pendirian ISP No. 9, tanggal 16 Agustus 2000, dibuat di hadapan Imran Ma\'aruf S.H., Notaris di Kota Bandar Lampung, dan telah mendapatkan pengesahan Menkumham berdasarkan Surat Keputusan No. C-1490 HT.01.01.TH 2001 Tahun 2001 tertanggal 28 Februari 2001, yang telah diumumkan dalam TBNRI No. 5902 pada BNRI No. 74, tanggal 14 September 2001 serta telah didaftarkan dalam Daftar Perseroan No. 303/BH.07.01/ VII/2001 tertanggal 17 Juli 2001 ("Akta Pendirian ISP").',
            'isp_business_purpose'   => "Maksud dan tujuan ISP berdasarkan Akta No. 38/2022 ialah:\n- Pertanian, Kehutanan dan Perikanan;\n- Perindustrian;\n- Perdagangan;",
            'conclusion'             => 'Background check menunjukkan seluruh pengurus dan pemegang saham utama berintegritas baik tanpa catatan negatif hukum.',
        ]);

        // =====================================================
        // 1130D: ENTITIES TREE / UBO
        // =====================================================
        $seedAnswers('1130D', [
            'ubo_name'      => 'Saimi Saleh',
            'entities_tree' => json_encode([
                ['pemilik' => 'Mr. Ibnu Syena Alfitra', 'dimiliki' => 'PT Indo American Seafoods Tbk', 'persentase' => 3.96],
                ['pemilik' => 'PT Indo American Foods', 'dimiliki' => 'PT Indo American Seafoods Tbk', 'persentase' => 69.24],
                ['pemilik' => 'Mr. Saimi Saleh', 'dimiliki' => 'PT Indo American Seafoods Tbk', 'persentase' => 5.94],
                ['pemilik' => 'Masyarakat (masing-masing dibawah 5%)', 'dimiliki' => 'PT Indo American Seafoods Tbk', 'persentase' => 20.86],
                ['pemilik' => 'PT Indo American Seafoods Tbk', 'dimiliki' => 'PT Indokom Samudera Persada', 'persentase' => 51.22],
            ]),
        ]);

        // =====================================================
        // 1200: KONFIRMASI INDEPENDENSI
        // =====================================================
        $seedAnswers('1200', [
            'confirmation_statement' => 'true',
            'team_declarations'      => json_encode([
                ['no' => 1, 'anggota_perikatan' => 'Maurice Ganda Nainggolan', 'jabatan' => 'Partner', 'tanggal_konfirmasi' => '2024-10-11', 'paraf' => ''],
                ['no' => 2, 'anggota_perikatan' => 'Artha Dame Marito', 'jabatan' => 'Quality Control (EQCR)', 'tanggal_konfirmasi' => '2024-10-11', 'paraf' => ''],
                ['no' => 3, 'anggota_perikatan' => 'Kenny Nurardi Wijaya', 'jabatan' => 'Manager', 'tanggal_konfirmasi' => '2024-10-10', 'paraf' => ''],
                ['no' => 4, 'anggota_perikatan' => 'Netty Lidya Simanjuntak', 'jabatan' => 'Senior Associate', 'tanggal_konfirmasi' => '2024-10-09', 'paraf' => ''],
                ['no' => 5, 'anggota_perikatan' => 'Raynaldi Tribuana', 'jabatan' => 'Senior Associate', 'tanggal_konfirmasi' => '2024-10-09', 'paraf' => ''],
                ['no' => 6, 'anggota_perikatan' => 'Daniel Gerald H', 'jabatan' => 'Junior Associate', 'tanggal_konfirmasi' => '2024-10-09', 'paraf' => ''],
                ['no' => 7, 'anggota_perikatan' => 'Irene Rizka Amalia', 'jabatan' => 'Junior Associate', 'tanggal_konfirmasi' => '2024-10-09', 'paraf' => ''],
                ['no' => 8, 'anggota_perikatan' => 'Aryn Sasikirana', 'jabatan' => 'Junior Associate', 'tanggal_konfirmasi' => '2024-10-09', 'paraf' => ''],
                ['no' => 9, 'anggota_perikatan' => 'Kurotun Ainiyah', 'jabatan' => 'Junior Associate', 'tanggal_konfirmasi' => '2024-10-09', 'paraf' => ''],
            ]),
        ]);

        // =====================================================
        // 1210: KUISIONER INDEPENDENSI
        // =====================================================
        $seedAnswers('1210', [
            'q1_public_company'      => 'Y',
            'q1_comment'             => 'Perseroan telah melakukan IPO sesuai surat pemberitahuan efektif OJK No. S-607/PM.02/2024 Tanggal 13 Juni 2024.',
            'q2_other_services'      => 'N',
            'q2_comment'             => 'Tidak ada jasa non-audit yang diberikan.',
            'q3_contingent_fees'     => 'N',
            'q3_comment'             => 'Tidak ada fee bersyarat atau referral fee.',
            'q4_self_review'         => 'N',
            'q4_comment'             => 'Tidak ada ancaman self review.',
            'q5_self_interest'       => 'N',
            'q5_comment'             => 'Tidak ada ancaman self interest.',
            'q6_code_compliance'     => 'Y',
            'q6_comment'             => 'Ya, Kami telah mematuhi Kode Etik Akuntan Publik sesuai dengan Seksi 290.',
            'q7_adequate_procedures' => 'Y',
            'q7_comment'             => 'Ya, Kami telah melakukan prosedur untuk mengeliminasi adanya ancaman dan menyatakan level independen memadai.',
        ]);

        // =====================================================
        // 1400: LAPORAN RISIKO
        // Sumber: 1400 Laporan Risiko PT IAS 2024.docx
        // Tabel: No | Deskripsi Risiko | Kesalahan yang mungkin terjadi | Dampak (H/L) | Probabilitas (H/L) | Risiko Signifikan? (Y/N) | Penetapan Asersi, Pervasif atau Seluruh Entitas | Ringkasan Pendekatan Audit | Ref
        // =====================================================
        $seedAnswers('1400', [
            'fs_risks' => json_encode([
                [
                    'deskripsi_risiko'    => 'Siapa yang bertanggung jawab atas tata kelola Perusahaan, kualifikasi, independensi, dan bagaimana mereka menjalankan fungsinya.',
                    'kesalahan'           => '',
                    'dampak'              => 'H',
                    'probabilitas'        => 'H',
                    'risiko_signifikan'   => 'Y',
                    'asersi'              => 'Pervasif',
                    'ringkasan_pendekatan'=> 'Manajemen Kunci Perusahaan meliputi jabatan Direktur Utama dan Direktur. Pengendali terakhir Perusahaan adalah Saimi Saleh. BOD dan Manajemen bertanggung jawab atas penyusunan dan penyajian wajar laporan keuangan sesuai dengan dan atas pengendalian internal untuk memungkinkan penyusunan laporan keuangan yang bebas dari kesalahan penyajian material, baik yang disebabkan oleh kecurangan maupun kesalahan. Untuk kualifikasinya, BOC dan BOD memiliki pengalaman kerja antara 15 – 39 tahun dengan tingkat Pendidikan SMP sampai S1.',
                    'ref'                 => 'Struktur Organisasi',
                ],
                [
                    'deskripsi_risiko'    => 'Code of Conduct Perusahaan yang mencerminkan penerapan etika Tone at the Top.',
                    'kesalahan'           => '',
                    'dampak'              => 'H',
                    'probabilitas'        => 'H',
                    'risiko_signifikan'   => 'Y',
                    'asersi'              => 'Pervasif',
                    'ringkasan_pendekatan'=> 'Pada tingkat Direksi, memberikan kebijakan dan PK (Perjanjian Kerja) untuk mencegah terjadinya kecurangan, tentunya dengan pengawasan. Selanjutnya adanya pengawasan berjenjang dari level Supervisor, Manager, hingga Direktur yang dilakukan secara berkala terhadap setiap fungsi yang ada di Perusahaan. Sebagai contoh, dilakukan pemisahan tugas dan reviu berjenjang pada setiap dokumen. Pimpinan Perusahaan menerapkan Tone at the Top dengan tidak melakukan tindakan / perbuatan yang melanggar untuk mencegah terjadinya fraud / kecurangan.',
                    'ref'                 => 'PK (Perjanjian Kerja), Form 1441 Penilaian Risiko Kecurangan',
                ],
                [
                    'deskripsi_risiko'    => 'Apakah ada kontrol untuk menghindari management override. Management override identik dengan manipulasi pencatatan akuntansi dan kecurangan pada laporan keuangan.',
                    'kesalahan'           => '',
                    'dampak'              => 'H',
                    'probabilitas'        => 'H',
                    'risiko_signifikan'   => 'Y',
                    'asersi'              => 'Pervasif',
                    'ringkasan_pendekatan'=> 'Informasi aktual terkait kecurangan dapat dideteksi melalui proses audit oleh auditor internal / komite audit dan biasanya dikomunikasikan secara berkala kepada direktur utama dan komisaris, sehingga disefisiensi atau kelemahan pada Perusahaan dapat segera dideteksi dan diantisipasi.',
                    'ref'                 => 'Form Testing Journal Entry',
                ],
                [
                    'deskripsi_risiko'    => 'Tes pengendalian terhadap pengakuan pendapatan.',
                    'kesalahan'           => '',
                    'dampak'              => 'H',
                    'probabilitas'        => 'H',
                    'risiko_signifikan'   => 'Y',
                    'asersi'              => 'Pervasif',
                    'ringkasan_pendekatan'=> 'Melakukan Prosedur Tes Of Control (TOC) terkait pengakuan penjualan dalam hal pengukuran, penyajian dan pengungkapan serta pisah batas (cut off) pengakuan pendapatan.',
                    'ref'                 => 'Form 2200.1 Uji Pengendalian_ToC & Tes D & I',
                ],
                [
                    'deskripsi_risiko'    => "Entri jurnal Non-standard (all): Untuk memastikan entri jurnal non-standard berada dalam kegiatan bisnis biasa.\n5 jurnal terakhir di bulan Juni 2024 termasuk JV: Untuk memastikan entri jurnal yang dibuat pada akhir tahun keuangan berkaitan dengan kegiatan bisnis biasa perusahaan.\n5 jurnal terakhir di bulan Desember 2024 termasuk JV: Untuk memastikan entri jurnal yang dibuat pada akhir tahun keuangan berkaitan dengan kegiatan bisnis biasa perusahaan.",
                    'kesalahan'           => '',
                    'dampak'              => 'H',
                    'probabilitas'        => 'H',
                    'risiko_signifikan'   => 'Y',
                    'asersi'              => 'Pervasif',
                    'ringkasan_pendekatan'=> "Tidak terdapat unusual journal entry terhadap:\nAkrual Bulanan untuk beban operasional dan beban penggajian\nPenyusutan bulanan\nReklasifikasi akun\nUang Muka\nPembelian",
                    'ref'                 => 'Form Testing Journal Entry',
                ],
                [
                    'deskripsi_risiko'    => 'Penerapan kebijakan akuntansi sesuai dengan Standar Akuntansi.',
                    'kesalahan'           => '',
                    'dampak'              => 'H',
                    'probabilitas'        => 'H',
                    'risiko_signifikan'   => 'Y',
                    'asersi'              => 'Pervasif',
                    'ringkasan_pendekatan'=> 'Perusahaan telah menetapkan kebijakan akuntansi yang sesuai dengan Standar Akuntansi Keuangan dan Manual Pedoman Akuntansi Keuangan Perusahaan.',
                    'ref'                 => 'Form 1450 Penilaian Risiko Akuntansi',
                ],
                [
                    'deskripsi_risiko'    => 'Pemberian bonus dan insentif yang mendorong terjadinya manipulasi dalam LK.',
                    'kesalahan'           => '',
                    'dampak'              => 'H',
                    'probabilitas'        => 'H',
                    'risiko_signifikan'   => 'Y',
                    'asersi'              => 'Pervasif',
                    'ringkasan_pendekatan'=> 'Menurut keterangan manajemen, Perusahaan memberikan remunerasi setiap tahunnya hanya kepada Dewan Komisaris dan Dewan Direksi.',
                    'ref'                 => 'LAI Notes 34 Transaksi dengan Pihak-pihak Berelasi',
                ],
                [
                    'deskripsi_risiko'    => 'Kecukupan dan kompetensi personel bidang akuntansi dalam penyusunan LK.',
                    'kesalahan'           => '',
                    'dampak'              => 'H',
                    'probabilitas'        => 'H',
                    'risiko_signifikan'   => 'Y',
                    'asersi'              => 'Pervasif',
                    'ringkasan_pendekatan'=> 'Personel yang bertanggung jawab sudah terlatih dan kompeten di bidangnya dengan latar belakang strata 1 untuk staff dan setara SMA/SMK untuk level admin. Setiap divisi, departemen, dan hub/cabang akan menginput data baik penjualan atau pembelian kedalam sistem Tally yang kemudian akan diverifikasi oleh Accounting dan lalu jika sudah sesuai dan di-approve akan di-posting dalam Tally dan akan diungkapkan dalam laporan keuangan.',
                    'ref'                 => '1430 Proses Pelaporan Keuangan',
                ],
            ]),
            'acct_risks' => json_encode([
                [
                    'deskripsi_risiko'    => 'Accounting Risk #1 Pencatatan cadangan kerugian penurunan nilai piutang',
                    'kesalahan'           => 'Manajemen berpotensi tidak memiliki asumsi dan dasar yang tepat dalam menentukan cadangan kerugian penurunan nilai piutang. Nilai pencadangan kerugian penurunan nilai piutang yang dicatatkan terlalu tinggi atau rendah daripada yang seharusnya.',
                    'dampak'              => 'H',
                    'probabilitas'        => 'H',
                    'risiko_signifikan'   => 'Y',
                    'asersi'              => "Piutang Usaha (Valuation)\nBeban kerugian penurunan nilai piutang (Valuation)\nTidak pervasif",
                    'ringkasan_pendekatan'=> "Melakukan interviu kepada manajemen terkait dasar penentuan nilai cadangan kerugian penurunan nilai piutang.\nMelakukan identifikasi dan tanya jawab terkait piutang usaha yang sudah lebih dari 90 hari dan tingkat kemungkinan pembayarannya.\nMelakukan perhitungan cadangan kerugian nilai piutang menggunakan dasar penentuan yang lebih komprehensif.\nMemeriksa subsequent event receipt setelah tanggal neraca apakah cukup lancar untuk menutupi piutang yang sudah default/macet.",
                    'ref'                 => '1450 Penilaian Risiko Akuntansi',
                ],
                [
                    'deskripsi_risiko'    => 'Accounting Risk #2 Pencatatan PSAK 24 tidak sesuai dengan siaran pers yang dilakukan IAI pada tahun 2024',
                    'kesalahan'           => 'Pencatatan liabilitas imbalan pascakerja berpotensi lebih tinggi, tidak sesuai dengan penjelasan IAI melalui siaran pers tentang penerapan PSAK 24',
                    'dampak'              => 'H',
                    'probabilitas'        => 'H',
                    'risiko_signifikan'   => 'Y',
                    'asersi'              => "Liabilitas Imbalan Pascakerja (Valuation),\nBeban Imbalan Pascakerja (Valuation)\nPenghasilan Komprehensif Lain (Valuation)\nTidak pervasif",
                    'ringkasan_pendekatan'=> "Melakukan komunikasi dengan manajemen dan aktuaris membahas siaran pers IAI tersebut dan pengaruhnya terhadap perhitungan liabilitas imbalan pascakerja yang dilakukan.\nMenganalisa asumsi yang digunakan oleh aktuaris untuk menghitung liabilitas imbalan pascakerja tersebut.",
                    'ref'                 => '1450 Penilaian Risiko Akuntansi',
                ],
                [
                    'deskripsi_risiko'    => 'Fraud Risk #1 Efektifitas Perusahaan dalam menjaga asetnya',
                    'kesalahan'           => 'Perusahaan perlu memperhatikan Langkah tepat dalam melakukan pengeluaran untuk barang modal dan perlu dilakukan pengawasan terhadap aset yang dibeli tersebut.',
                    'dampak'              => 'L',
                    'probabilitas'        => 'H',
                    'risiko_signifikan'   => 'N',
                    'asersi'              => "Piutang Usaha (Valuation, Existence)\nPenjualan aset tetap (Existence dan valuation)\nTidak pervasif",
                    'ringkasan_pendekatan'=> "Peroleh SOP terkait pengadaan barang dan penjualan barang.\nTanya jawab dengan auditor internal.\nLakukan pengecekan terhadap dokumen pendukung sehubungan dengan pembelian dan penjualan aset tetap tersebut.\nMeminta laporan auditor internal sehubungan dengan hasil temuannya selama tahun 2024.\nMeminta PK terkait aset tetap.",
                    'ref'                 => '1440 Penilaian Risiko Kecurangan',
                ],
                [
                    'deskripsi_risiko'    => 'Fraud Risk #2 Penyalahgunaan aset oleh karyawan dan manajemen',
                    'kesalahan'           => 'Penyalahgunaan aset oleh bagian distribusi bisa saja terjadi dikarenakan belum ada',
                    'dampak'              => 'L',
                    'probabilitas'        => 'H',
                    'risiko_signifikan'   => 'N',
                    'asersi'              => 'Tidak pervasif',
                    'ringkasan_pendekatan'=> "Melakukan interviu dengan auditor internal sehubungan dengan hasil pemeriksaannya selama tahun 2024.\nMeminta laporan auditor internal sehubungan dengan hasil temuannya selama tahun 2024.",
                    'ref'                 => '1440 Penilaian Risiko Kecurangan',
                ],
                [
                    'deskripsi_risiko'    => 'Accounting Risk #3 Fraud Risk #3 Fraudulent Financial Reporting',
                    'kesalahan'           => 'Manajemen mempunyai target yang sudah ditentukan bersama sebelumnya Untuk mencapai net income tersebut, dimulai dari perolehan project baru yang diikuti oleh pengakuan pendapatan.',
                    'dampak'              => 'H',
                    'probabilitas'        => 'H',
                    'risiko_signifikan'   => 'N',
                    'asersi'              => 'Tidak pervasif',
                    'ringkasan_pendekatan'=> "Melakukan kunjungan ke kantor pusat.\nMelakukan tanya jawab sehubungan dengan pengakuan penjualan",
                    'ref'                 => '1440 Penilaian Risiko Kecurangan',
                ],
            ]),
        ]);

        // =====================================================
        // 1410: PEMAHAMAN PERATURAN & KONTROL LEGALITAS
        // =====================================================
        $seedAnswers('1410', [
            'regulations_table'                   => "1. PP No. 5/2021 (Perizinan Berbasis Risiko) | NIB 8120003801742 | Patuh\n2. PER-02/PJ/2018 (NPWP) | NPWP 02.523.050.9-325.000 | Patuh\n3. PMK 147/PMK.03/2017 (SPPKP) | S-727PKPWPJ.28/KP.102021 | Patuh\n4. PER 04/PJ/2020 (TDP) | TDP 07044110000710 | Patuh\n5. Permendag 07/2017 (SIUP) | 503/02-SIUP.FL/IV.20/2019 | Patuh\n6. UU 32/2009 (Izin Lingkungan) | SK BLHD 660/257.a/IV.03/05/2014 | Patuh\n7. UU 32/2009 Pasal 59 (Izin TPS LB3) | 503/32/IV.17/SK/LB3/X/2020 | Patuh\n8. POJK No. 55/2015 (Komite Audit) | SK 10/SKGCG/IAS/LGL/III/2024 | Patuh\n9. Permendag 07/2017 (IUI) | 503/22/IV.17/IUI/XI/DU/2018 | Patuh\n10. Perpres 80/2017 (BPOM Udang Beku) | PN.06.05.52.03.19.1281 | Patuh\n11. Perpres 80/2017 (BPOM Bakso Udang) | PN.06.05.52.03.19.0720 | Patuh\n12. Perpres 80/2017 (BPOM Nugget Udang) | PN.06.05.52.03.19.9950 | Patuh\n13. Perpres 80/2017 (BPOM Udang Tepung) | PN.06.05.52.03.19.0371 | Patuh\n14. Perpres 80/2017 (BPOM Tempura) | PN.06.05.52.03.19.4570 | Patuh\n15. Permen KP 17/2019 (SKP) | 15137/18/SKP/BK/VII/2020 | Patuh\n16. BAP Standard (Global Seafood) | ID22/00000302 | Patuh\n17. PP 57/2015 (HACCP) | 056/PM/HACCP/PB/11/21 | Patuh\n18. BRC Food Safety (Intertek) | 401A2011001 | Patuh\n19. US FDA Registration | 17419801194 | Patuh",
            'conclusion_compliance'              => 'Entitas secara konsisten mematuhi seluruh ketentuan perizinan industri pengolahan makanan, lingkungan hidup, perpajakan, dan sertifikasi mutu ekspor internasional.',
            'ojk_regulations_table'               => "1. POJK 33/POJK.04/2014: Direksi & Dewan Komisaris | Terpenuhi | Patuh\n2. POJK 55/POJK.04/2015: Pembentukan Komite Audit | Terpenuhi (1 Ketua + 2 Anggota) | Patuh\n3. POJK 35/POJK.04/2014: Sekretaris Perusahaan | Terpenuhi | Patuh\n4. POJK 31/POJK.04/2015: Keterbukaan Informasi Fakta Material | Terpenuhi | Patuh",
            'directors_commissioners_compliance'  => 'Susunan Direksi (3 orang) dan Dewan Komisaris (2 orang dengan 1 Komisaris Independen) telah memenuhi POJK No. 33/2014.',
            'audit_committee_compliance'          => 'Komite Audit diketuai oleh Komisaris Independen (Dr. Leo Herlambang) dengan piagam komite audit yang sah.',
            'corporate_secretary_compliance'      => 'Sekretaris Perusahaan telah ditunjuk dan menjalankan fungsi kepatuhan pelaporan BEI & OJK.',
            'legalitas_conclusion'                => 'Seluruh kewajiban regulasi pasar modal dan OJK telah dipenuhi secara patuh.',
        ]);

        // =====================================================
        // 1420: PROSEDUR ANALITIK AWAL (PAR)
        // =====================================================
        $seedAnswers('1420', [
            // Section: Parameter
            'basis_laporan'                => 'Laporan Keuangan Konsolidasian Interim per September 2024 & Unaudited Desember 2024',
            'pm_threshold'                 => '253107000',
            'significant_change_pct'       => '20%',
            // Section: Laba Rugi
            'par_penjualan'                => '2024: Rp 160.540.499.628 | 2023: Rp 135.210.000.000 | +Rp 25.330.499.628 (+18.7%) | Peningkatan volume ekspor udang beku pasca-IPO.',
            'par_hpp'                      => '2024: Rp 107.535.872.927 | 2023: Rp 92.100.000.000 | +Rp 15.435.872.927 (+16.8%) | Sejalan dengan kenaikan volume produksi dan harga pakan.',
            'par_beban_penjualan'          => '2024: Rp 13.450.215.380 | 2023: Rp 11.200.000.000 | +Rp 2.250.215.380 (+20.1%) | Kenaikan biaya logistik & freight ekspor.',
            'par_beban_umum'               => '2024: Rp 26.450.215.280 | 2023: Rp 22.800.000.000 | +Rp 3.650.215.280 (+16.0%) | Kenaikan beban gaji & biaya kepatuhan emiten baru.',
            'par_pendapatan_lain'          => '2024: Rp 2.273.092.655 | 2023: Rp 1.500.000.000 | +Rp 773.092.655 (+51.5%) | Keuntungan selisih kurs penguatan USD.',
            'par_beban_bunga'              => '2024: Rp 12.487.102.791 | 2023: Rp 10.100.000.000 | +Rp 2.387.102.791 (+23.6%) | Bunga fasilitas kredit modal kerja.',
            // Section: Neraca
            'par_kas_bank'                 => '2024: Rp 13.187.388.399 | 2023: Rp 8.500.000.000 | +Rp 4.687.388.399 (+55.1%) | Sisa dana hasil penawaran umum perdana (IPO).',
            'par_piutang_usaha_ketiga'     => '2024: Rp 37.856.978.018 | 2023: Rp 31.200.000.000 | +Rp 6.656.978.018 (+21.3%) | Kenaikan sejalan dengan penjualan ekspor kuartal IV.',
            'par_piutang_usaha_berelasi'   => '2024: Rp 339.894.481 | 2023: Rp 450.000.000 | -Rp 110.105.519 (-24.5%) | Pelunasan bertahap oleh entitas terafiliasi.',
            'par_piutang_lain'             => '2024: Rp 1.250.000.000 | 2023: Rp 980.000.000 | +Rp 270.000.000 (+27.6%) | Piutang karyawan dan uang jaminan sewa.',
            'par_persediaan'               => '2024: Rp 262.102.659.717 | 2023: Rp 225.000.000.000 | +Rp 37.102.659.717 (+16.5%) | Akumulasi stok udang beku untuk pesanan awal tahun.',
            'par_aset_biologis'            => '2024: Rp 3.489.807.904 | 2023: Rp 2.800.000.000 | +Rp 689.807.904 (+24.6%) | Pertumbuhan biomassa tambak udang siklus berjalan.',
            'par_pajak_dimuka'             => '2024: Rp 11.345.112.342 | 2023: Rp 9.800.000.000 | +Rp 1.545.112.342 (+15.8%) | Klaim restitusi PPN yang belum dicairkan.',
            'par_biaya_dimuka'             => '2024: Rp 1.488.682.854 | 2023: Rp 1.200.000.000 | +Rp 288.682.854 (+24.1%) | Premi asuransi dan uang muka sewa tambak.',
            'par_aset_tetap'               => '2024: Rp 68.366.993.855 | 2023: Rp 65.100.000.000 | +Rp 3.266.993.855 (+5.0%) | Penambahan mesin processing cold storage baru.',
            'par_aset_hak_guna'            => '2024: Rp 7.635.332.866 | 2023: Rp 8.200.000.000 | -Rp 564.667.134 (-6.9%) | Amortisasi hak guna sewa tambak (PSAK 73).',
            'par_utang_usaha'              => '2024: Rp 10.645.094.570 | 2023: Rp 9.200.000.000 | +Rp 1.445.094.570 (+15.7%) | Utang pembelian pakan dan bahan baku udang.',
            'par_utang_bank'               => '2024: Rp 900.894.064 | 2023: Rp 1.500.000.000 | -Rp 599.105.936 (-39.9%) | Pembayaran pokok cicilan kredit bank.',
            'par_beban_akrual'             => '2024: Rp 4.949.487.657 | 2023: Rp 4.100.000.000 | +Rp 849.487.657 (+20.7%) | Akrual gaji, bonus, dan beban operasional Desember.',
            'par_utang_pajak'              => '2024: Rp 4.409.068.751 | 2023: Rp 3.600.000.000 | +Rp 809.068.751 (+22.5%) | Kenaikan PPh Badan dan PPN terutang.',
            'par_utang_aset_tetap'         => '2024: Rp 19.446.177 | 2023: Rp 150.000.000 | -Rp 130.553.823 (-87.0%) | Pelunasan cicilan leasing mesin.',
            'par_modal'                    => '2024: Rp 69.500.000.000 | 2023: Rp 55.000.000.000 | +Rp 14.500.000.000 (+26.4%) | Tambahan modal disetor dari IPO 290jt lembar.',
            'par_akun_lain'                => '2024: Rp 9.955.987.332 (Imbalan Pascakerja) | 2023: Rp 8.800.000.000 | +Rp 1.155.987.332 (+13.1%) | Penyesuaian perhitungan aktuaris tahun berjalan.',
            // Section: Kesimpulan
            'par_conclusion'               => 'Fluktuasi akun-akun utama sejalan dengan pertumbuhan bisnis pasca-IPO. Akun berisiko tinggi (Piutang, Persediaan, Aset Biologis, Pendapatan) menjadi fokus prosedur substantif.',
        ]);

        // =====================================================
        // 1430: PROSES PELAPORAN KEUANGAN
        // =====================================================
        $seedAnswers('1430', [
            'q1_competent_personnel'       => 'Y', 'q1_comment' => 'Tim akuntansi dipimpin oleh Accounting Manager berpengalaman dengan latar belakang S1 Akuntansi. Input transaksi dilakukan oleh staf terlatih dengan supervisi berjenjang.',
            'q2_preparation_process'       => 'Y', 'q2_comment' => 'Proses penyusunan LK menggunakan Tally ERP sebagai sumber data; closing dilakukan manual di spreadsheet dan diposting ke Tally setelah diverifikasi.',
            'q3_senior_review'             => 'Y', 'q3_comment' => 'Laporan keuangan interim direview oleh Direktur Keuangan dan final LK tahunan diserahkan kepada Direktur Utama untuk persetujuan.',
            'q4_segment_reporting'         => 'Y', 'q4_comment' => 'Perusahaan mengidentifikasi satu segmen operasi utama: pengolahan dan penjualan udang beku.',
            'q5_subsidiary_process'        => 'Y', 'q5_comment' => 'LK PT Indokom Samudra Persada (anak) disusun secara independen dan dikonsolidasikan oleh tim akuntansi induk.',
            'q6_adjustment_responsibility' => 'Y', 'q6_comment' => 'Penyesuaian jurnal akhir periode menjadi tanggung jawab Accounting Manager dengan otorisasi Direktur Keuangan.',
            'q7_adjustment_basis'          => 'Y', 'q7_comment' => 'Dasar penyesuaian adalah kebijakan akuntansi SAK Umum, termasuk akrual beban gaji, penyusutan, dan estimasi aktuaria imbalan pascakerja.',
            'q8_adjustment_completeness'   => 'Y', 'q8_comment' => 'Kelengkapan penyesuaian dipastikan melalui checklist closing bulanan dan review jadwal akrual oleh Accounting Manager.',
            'q9_intragroup_elimination'    => 'Y', 'q9_comment' => 'Eliminasi transaksi intra-grup dilakukan secara manual menggunakan daftar saldo intercompany; dipastikan nihil setelah rekonsiliasi.',
            'q10_adjustment_accuracy'      => 'Y', 'q10_comment' => 'Akurasi penyesuaian dikontrol dengan four-eyes principle: diinput oleh staf, direview oleh manager, dan disetujui Direktur Keuangan.',
            'q11_nonstandard_journal'      => 'N', 'q11_comment' => 'Tidak ada entri jurnal non-standar yang signifikan diluar jurnal penyesuaian akrual rutin.',
            'q12_minority_arrangement'     => 'Y', 'q12_comment' => 'Kepentingan non-pengendali (NCI) sebesar 48.78% pada PT Indokom Samudra Persada disajikan terpisah di ekuitas konsolidasian.',
            'q13_accounting_policy_uniform' => 'Y', 'q13_comment' => 'Kebijakan akuntansi seragam telah diterapkan pada entitas induk dan anak sesuai SAK Umum dan Manual Akuntansi Keuangan Perusahaan.',
            'q14_policy_reconciliation'    => 'Y',
            'q15_complete_reporting'       => 'Y',
            'q16_foreign_currency'         => 'Y',
            'q17_experienced_staff'        => 'Y',
            'q18_manual_or_system'         => 'Y', 'q18_comment' => 'Tally ERP — digunakan untuk pencatatan seluruh transaksi; laporan keuangan disusun dari data Tally yang diekspor ke Excel.',
            'q19_data_transfer'            => 'Y',
            'q20_applicable_standards'     => 'Y', 'q20_comment' => 'Perusahaan memastikan kepatuhan terhadap SAK Umum melalui reviu berkala oleh Accounting Manager dan konsultasi dengan auditor eksternal.',
        ]);

        // =====================================================
        // 1440: FRAUD RISK ASSESSMENT
        // =====================================================
        $seedAnswers('1440', [
            'a1_management_incentive'        => 'N',
            'a1_explanation'                 => 'Stabilitas keuangan kuat didukung dana IPO. Tidak ada tekanan mencapai target keuangan yang tidak realistis.',
            'a2_management_opportunity'      => 'N',
            'a2_explanation'                 => 'Pemisahan fungsi dan pengawasan oleh Komite Audit independen berjalan memadai. Transaksi perbankan memerlukan dual authorization.',
            'a3_attitude_rationalization'    => 'N',
            'a3_explanation'                 => 'Tone at the top dari Direksi dan Dewan Komisaris sangat mengedepankan kepatuhan regulasi OJK/BEI dan integritas bisnis.',
            'b1_asset_incentive'             => 'N',
            'b1_explanation'                 => 'Kompensasi karyawan memadai sesuai standar UMR dan terdapat insentif kinerja rutin.',
            'b2_asset_rationalization'       => 'N',
            'b2_explanation'                 => 'Cold storage dan area produksi dijaga ketat dengan CCTV 24 jam, logistik diikat SOP pengiriman, dan stock opname berkala.',
            'c_interviewees'                 => "1. Ibnu Syena Alfitra (Direktur Utama)\n2. Abu Yazid (Direktur Keuangan)\n3. Dr. Leo Herlambang (Komisaris Independen / Ketua Komite Audit)",
            'c_discussion_notes'             => 'Hasil diskusi menyimpulkan bahwa pengendalian internal terbukti efektif dalam memitigasi risiko fraud, ketiadaan transaksi non-standar, dan adanya saluran pengaduan (whistleblowing).',
            'fraud_conclusion'               => 'Risiko salah saji material akibat kecurangan (fraud risk) secara keseluruhan dinilai RENDAH (Low). Tidak ditemukan indikasi keterlibatan manajemen dalam fraud.',
        ]);

        // =====================================================
        // 1441: INTERVIEW KLIEN - RISIKO KECURANGAN
        // =====================================================
        $seedAnswers('1441', [
            'interviewees_list'           => "1. Ibnu Syena Alfitra — Direktur Utama (Wawancara: 10 Okt 2024)\n2. Abu Yazid — Direktur Keuangan (Wawancara: 10 Okt 2024)\n3. Dr. Leo Herlambang — Komisaris Independen / Ketua Komite Audit (Wawancara: 11 Okt 2024)\n4. Ibnu Surya Ramadhan — Direktur Operasional (Wawancara: 11 Okt 2024)",
            'qa_fraud_risk_assessment'    => 'Manajemen menilai risiko fraud di level operasional telah termitigasi melalui pengawasan berlapis dan audit internal independen.',
            'qa_actual_fraud_knowledge'   => 'Tidak ada pengetahuan, dugaan, atau laporan mengenai tindak kecurangan aktual baik dari pihak internal maupun eksternal selama tahun 2024.',
            'qa_oversight_involvement'    => 'Komite Audit melakukan rapat berkala tiap kuartal untuk mengawasi laporan keuangan interim dan efektivitas pengendalian internal.',
            'qa_additional'               => 'Sistem pelaporan pelanggaran (Whistleblowing System) aktif dan dapat diakses langsung oleh Komite Audit.',
            'interview_conclusion'        => 'Manajemen dan TCWG menunjukkan komitmen tinggi terhadap transparansi, pengendalian internal, dan pencegahan tindak kecurangan.',
        ]);

        // =====================================================
        // 1450: PENILAIAN RISIKO BISNIS
        // =====================================================
        $seedAnswers('1450', [
            'a1_1_industry_characteristics' => 'RMM: Yes | Industri pengolahan hasil laut sangat bergantung pada pasokan udang segar dan standar mutu internasional (FDA, BRC, HACCP).',
            'a1_2_industry_cycle'           => 'RMM: No | Industri berada pada fase pertumbuhan dengan permintaan ekspor stabil ke pasar AS dan Jepang.',
            'a1_3_seasonal_impact'          => 'RMM: Yes | Hasil panen tambak udang dipengaruhi oleh cuaca, suhu air, dan siklus musim hujan yang dapat mempengaruhi harga bahan baku.',
            'a1_4_technology_product'       => 'RMM: No | Teknologi pengolahan udang beku (IQF) stabil dan teruji.',
            'a1_5_technology_production'    => 'RMM: No | Menggunakan mesin cold storage modern dengan genset cadangan otomatis.',
            'b2_1_regulations'              => 'RMM: Yes | Kepatuhan terhadap regulasi OJK sebagai emiten baru dan regulasi ekspor karantina perikanan.',
            'b2_2_taxation'                 => 'RMM: Yes | Restitusi PPN ekspor dan perhitungan PPh Badan dengan kepatuhan penuh.',
            'b2_3_environmental'            => 'RMM: Yes | Pengelolaan IPAL dan izin TPS limbah B3 sesuai AMDAL dan regulasi KLHK.',
            'c3_1_business_operations'      => 'RMM: Yes | Operasi terintegrasi: tambak udang (aset biologis) -> cold storage processing -> ekspor via Pelabuhan Panjang.',
            'c3_2_revenue_nature'           => 'RMM: Yes | Mayoritas pendapatan (>90%) berasal dari ekspor udang beku dalam mata uang USD.',
            'c3_3_group_structure'          => 'RMM: Yes | Kepemilikan 51.22% pada PT Indokom Samudra Persada (ISP) memerlukan konsolidasi dan eliminasi transaksi intercompany.',
            'd_products_markets'            => 'RMM: Yes | Produk utama: Frozen Raw Shrimp, Cooked Shrimp, Breaded Shrimp. Pasar ekspor: AS (65%), Jepang (20%), Uni Eropa (10%), Domestik (5%).',
            'e5_1_facilities'               => 'RMM: No | Pabrik berlokasi di Lampung Selatan dengan kapasitas cold storage 3.000 ton dalam kondisi operasional sangat baik.',
            'f6_1_debt_structure'           => 'RMM: Yes | Fasilitas pinjaman bank modal kerja dengan covenant rasio likuiditas dan coverage rasio yang harus dipenuhi.',
            'f6_2_leasing'                  => 'RMM: No | Nilai sewa pembiayaan kecil dan telah disajikan sesuai PSAK 73.',
            'f6_3_beneficial_owner'         => 'RMM: Yes | UBO adalah H. Saimi Saleh melalui PT Indo American Food; seluruh transaksi pihak berelasi diungkapkan secara memadai.',
            'g7_1_objectives'               => 'RMM: No | Strategi pasca-IPO difokuskan pada peningkatan kapasitas cold storage dan penetrasi pasar ekspor baru.',
            'business_risk_conclusion'      => 'Risiko bisnis entitas secara keseluruhan berada pada tingkat SEDANG (Medium) dengan mitigasi risiko operasional dan kepatuhan regulasi yang memadai.',
        ]);

        // =====================================================
        // 1460: PENGENDALIAN INTERNAL ENTITAS
        // =====================================================
        $seedAnswers('1460', [
            'q1_internal_audit_adequate' => 'NA',
            'q1_comment'                  => 'Pekerjaan internal auditor / komite audit telah memadai dengan dasar pembentukan Pasal 12 POJK No. 55/2015.',
            'q2_usable_as_evidence'      => 'NA',
            'q2_comment'                  => 'Eksternal Auditor tidak menggunakan hasil dari pekerjaan internal auditor sebagai bukti langsung pengujian substantif.',
            'q3_adequate_for_external'   => 'NA',
            'q3_comment'                  => 'Eksternal Auditor melakukan pengujian independen secara mandiri.',
            'q4_conclusion'              => 'NA',
            'q4_comment'                  => 'Eksternal Auditor tidak mengandalkan pekerjaan internal audit, seluruh pengujian dilakukan oleh tim perikatan KAP MGN.',
        ]);

        // =====================================================
        // 1500: PENILAIAN RISIKO TINGKAT LK PER AKUN
        // =====================================================
        $seedAnswers('1500', [
            'overall_materiality_info' => 'Overall Materiality is Rp 316.384.000 and planned performance materiality (80% for low risk) is Rp 253.107.000.',
            'acct_kas_bank'            => "Nilai: Rp 13.187.388.399 | Material: Y | Asersi: E, A, P | Risk: Medium | Alasan: Potensi salah saji/klasifikasi | Strategi: Konfirmasi bank, cek rekening koran, cash opname, cek sertifikat deposito.",
            'acct_piutang_usaha'       => "Nilai: Rp 37.856.978.018 | Material: Y | Asersi: E, V, C | Risk: High | Alasan: CKPN & pengakuan piutang | Strategi: Aging piutang, konfirmasi sampling, subsequent receipts, hitung ulang CKPN.",
            'acct_piutang_lain'        => "Nilai: Rp 339.894.481 | Material: Y | Asersi: E, V, C | Risk: High | Alasan: Transaksi pihak berelasi | Strategi: Pelajari perjanjian, konfirmasi, subsequent receipts.",
            'acct_persediaan'          => "Nilai: Rp 262.102.659.717 | Material: Y | Asersi: E, V, A, C | Risk: High | Alasan: Nilai material, LCNRV | Strategi: Stock opname 31 Des 2024, uji net realizable value, interviu kontrol persediaan.",
            'acct_aset_biologis'       => "Nilai: Rp 3.489.807.904 | Material: Y | Asersi: E, A | Risk: Medium | Alasan: Penilaian PSAK 69 | Strategi: Cek fisik tambak, sampling benur/pakan, hitung gain/loss nilai wajar siklus berjalan.",
            'acct_uang_muka'           => "Nilai: Rp 1.488.682.854 | Material: N | Asersi: A, E | Risk: Medium | Alasan: Uang muka lama | Strategi: Vouching sampling, periksa dokumen pendukung.",
            'acct_pajak_dimuka'        => "Nilai: Rp 11.345.112.342 | Material: N | Asersi: A, V | Risk: High | Alasan: Restitusi PPN | Strategi: Rekapitulasi dan rekonsiliasi SPT Masa PPN.",
            'acct_aset_tetap'          => "Nilai: Rp 68.366.993.855 | Material: Y | Asersi: E, A, V | Risk: High | Alasan: Nilai material & revaluasi tanah | Strategi: Hitung ulang penyusutan, cek penambahan/penjualan, reviu laporan appraisal.",
            'acct_aset_hak_guna'       => "Nilai: Rp 7.635.332.866 | Material: Y | Asersi: C, E, A | Risk: High | Alasan: PSAK 73 Sewa | Strategi: Pelajari kontrak sewa tambak, hitung ulang amortisasi hak guna.",
            'acct_pajak_tangguhan'     => "Nilai: Rp 1.790.182.139 | Material: Y | Asersi: C, E, A | Risk: Medium | Alasan: Beda temporer | Strategi: Minta rincian & reviu perhitungan pajak tangguhan.",
            'acct_utang_usaha'         => "Nilai: Rp 10.645.094.570 | Material: Y | Asersi: E, V, C | Risk: High | Alasan: Completeness utang | Strategi: Aging utang, konfirmasi supplier, cek pembayaran subsequent.",
            'acct_beban_akrual'        => "Nilai: Rp 4.949.487.657 | Material: Y | Asersi: E | Risk: Medium | Alasan: Dasar akrual | Strategi: Cek dasar asumsi beban akrual dan vouching.",
            'acct_utang_pajak'         => "Nilai: Rp 4.409.068.751 | Material: Y | Asersi: V, A | Risk: High | Alasan: Kepatuhan PPh & PPN | Strategi: Periksa SPT Masa & SSP PPh 21, 23, PPN 2024.",
            'acct_utang_bank'          => "Nilai: Rp 900.894.064 | Material: Y | Asersi: E, A, V | Risk: High | Alasan: Perjanjian kredit | Strategi: Konfirmasi bank, hitung ulang pokok & bunga, pelajari covenant.",
            'acct_utang_aset_tetap'    => "Nilai: Rp 19.446.177 | Material: N | Asersi: E, A | Risk: Medium | Alasan: Utang leasing | Strategi: Hitung ulang saldo cicilan pokok.",
            'acct_imbalan_pascakerja'  => "Nilai: Rp 9.955.987.332 | Material: Y | Asersi: C, P | Risk: High | Alasan: Asumsi aktuaris PSAK 24 | Strategi: Dapatkan laporan aktuaris, uji asumsi rasionalitas, cek realisasi pesangon.",
            'acct_modal'               => "Nilai: Rp 69.500.000.000 | Material: Y | Asersi: A, E, P | Risk: Medium | Alasan: Perubahan modal IPO | Strategi: Cek daftar pemegang saham per 31 Des 2024, summary akta terbaru.",
            'acct_tambahan_modal'      => "Nilai: Rp 104.623.471.870 | Material: Y | Asersi: V, A | Risk: Low | Alasan: Agio saham IPO | Strategi: Pelajari perhitungan agio saham hasil IPO dan proforma entitas anak.",
            'acct_oci'                 => "Nilai: (Rp 337.072.852) | Material: N | Asersi: V, A | Risk: Medium | Alasan: Pengukuran aktuarial | Strategi: Pelajari laporan aktuaria sehubungan PSAK 24 OCI.",
            'acct_saldo_laba'          => "Nilai: Rp 6.617.104.927 | Material: Y | Asersi: E | Risk: Low | Alasan: Akumulasi laba | Strategi: Cocokkan saldo awal dengan LK audited tahun lalu.",
            'acct_penjualan'           => "Nilai: Rp 160.540.499.628 | Material: Y | Asersi: A, E, C, V | Risk: High | Alasan: Pengakuan & cut-off | Strategi: Vouching dokumen ekspor (PEB/BL), rekonsiliasi PPN, cut-off testing.",
            'acct_cogs'                => "Nilai: (Rp 107.535.872.927) | Material: Y | Asersi: E, C, V | Risk: High | Alasan: Alokasi biaya produksi | Strategi: Uji perhitungan beban langsung, sampling invoice bahan baku, cut-off.",
            'acct_beban_umum'          => "Nilai: (Rp 26.450.215.280) | Material: Y | Asersi: E, A | Risk: Medium | Alasan: Klasifikasi beban | Strategi: Vouching operating expenses dengan metode sampling.",
            'acct_beban_penjualan'     => "Nilai: (Rp 13.450.215.380) | Material: Y | Asersi: E, A | Risk: Medium | Alasan: Biaya angkut ekspor | Strategi: Vouching bukti pengapalan dan jasa freight.",
            'acct_pendapatan_lain'     => "Nilai: Rp 2.273.092.655 | Material: Y | Asersi: E | Risk: Low | Alasan: Selisih kurs | Strategi: Uji petik bukti penerimaan dan perhitungan selisih kurs.",
            'acct_beban_bunga'         => "Nilai: (Rp 12.487.102.791) | Material: Y | Asersi: E | Risk: High | Alasan: Bunga pinjaman | Strategi: Rekonsiliasi dengan rekening koran dan konfirmasi bank.",
        ]);

        // =====================================================
        // 1600: PENENTUAN MATERIALITAS
        // =====================================================
        $seedAnswers('1600', [
            'main_users'                    => 'Pemegang Saham Publik, Kreditur Bank, Otoritas Jasa Keuangan (OJK), dan Bursa Efek Indonesia (BEI).',
            'qualitative_factors'           => 'Tidak ada faktor kualitatif khusus yang memerlukan penyesuaian materialitas ke tingkat lebih rendah.',
            'benchmark_aset_pct'            => 'N/A',
            'benchmark_pendapatan_pct'      => '2%',
            'benchmark_aset_bersih_pct'     => 'N/A',
            'benchmark_ebit_pct'            => 'N/A',
            'benchmark_other'               => 'N/A',
            'benchmark_justification'       => 'Laba sebelum pajak mempersentasekan jumlah paling kecil dibandingkan akun lain, sehingga detail-detail atas materialitas yang didapat atas setiap akun bisa lebih mendalam untuk diteliti lebih lanjut.',
            'ebit_current'                  => '3172672549',
            'ebit_prior'                    => '1332019711',
            'overall_materiality_value'     => '316384000',
            'performance_materiality_pct'   => '80%',
            'performance_materiality_value' => '253107000',
            'specific_pm_qualitative'       => 'N',
            'specific_pm_detail'            => 'Not Applicable',
            'mud_threshold'                 => '15819000',
        ]);

        // =====================================================
        // 1610: MATERIALITY SAMPLING
        // =====================================================
        $seedAnswers('1610', [
            // Section: Informasi Dasar
            'closing_date'                  => '2024-12-31',
            'materiality_basis'             => 'Income Before Tax',
            'basis_justification'           => 'Laba sebelum pajak dipilih sebagai basis karena merupakan indikator kinerja paling relevan bagi pengguna LK (investor publik dan kreditur) dan memberikan threshold lebih konservatif.',
            'basis_value_idr'               => '3172672549',
            'exchange_rate'                 => '15140',
            'basis_value_usd'               => '209554',
            // Section: Sliding Scale & Materialitas
            'sliding_scale_table'           => "Range USD 50,000 – 99,999 => 10.0%\nRange USD 100,000 – 499,999 => 5.0% – 10.0%\nRange USD 500,000+ => 3.0% – 5.0%\nBasis Value: USD 209,554 → Applicable Range: 100K-500K",
            'applicable_percentage'         => '10% (batas atas range untuk perusahaan baru terdaftar di BEI dengan risiko inherent tinggi)',
            'overall_materiality_usd'       => '20900',
            'overall_materiality_idr'       => '316384000',
            'client_risk_classification'    => 'Non High Risk',
            'performance_materiality_pct'   => '80%',
            'performance_materiality_usd'   => '16720',
            'performance_materiality_idr'   => '253107000',
            // Section: MUD & Catatan
            'tolerable_misstatement_idr'    => '253107000',
            'mud_pct'                       => '5%',
            'mud_usd'                       => '1045',
            'mud_idr'                       => '15819000',
            'materiality_notes'             => 'Materialitas dihitung berdasarkan Laba Sebelum Pajak konsolidasian. Performance Materiality 80% karena klien dikategorikan Non High Risk. MUD ditetapkan 5% dari Overall Materiality untuk akumulasi unadjusted differences.',
        ]);

        // =====================================================
        // 1700: ALOKASI JAM JASA & PERENCANAAN AUDIT
        // =====================================================
        $seedAnswers('1700', [
            'nama_kap'             => 'KAP Maurice Ganda Nainggolan dan Rekan',
            'nama_ap'              => 'Maurice Ganda Nainggolan, S.E., CPA',
            'nama_klien'           => 'PT Indo American Seafoods Tbk dan Entitas Anak',
            'alamat_klien'         => 'Jl. Ir. Sutami Km. 13 Desa Sukanegara, Kec. Tanjung Bintang, Kab. Lampung Selatan',
            'tahun_buku'           => '31 Desember 2024',
            'tanggal_mulai'        => '2024-12-02',
            'tanggal_akhir'        => '2024-12-06',
            'jam_mulai'            => '08:30 WIB',
            'jam_pulang'           => '17:30 WIB',
            'hari_libur'           => '2',
            'jam_efektif_per_hari' => '8',
            'team_allocation'      => "1. Maurice Ganda Nainggolan | Engagement Partner | 20 Jam | 30 Jam | 30 Jam (Total 80 Jam)\n2. Artha Dame Marito | EQCR / QC Partner | 10 Jam | 15 Jam | 15 Jam (Total 40 Jam)\n3. Kenny Nurardi Wijaya | Audit Manager | 40 Jam | 80 Jam | 40 Jam (Total 160 Jam)\n4. Netty Lidya Simanjuntak | Senior Associate | 40 Jam | 100 Jam | 40 Jam (Total 180 Jam)\n5. Raynaldi Tribuana | Senior Associate | 40 Jam | 100 Jam | 40 Jam (Total 180 Jam)\n6. Daniel Gerald H | Junior Associate | 30 Jam | 120 Jam | 30 Jam (Total 180 Jam)\n7. Irene Rizka Amalia | Junior Associate | 30 Jam | 120 Jam | 30 Jam (Total 180 Jam)\n8. Aryn Sasikirana | Junior Associate | 30 Jam | 120 Jam | 30 Jam (Total 180 Jam)\n9. Kurotun Ainiyah | Junior Associate | 30 Jam | 120 Jam | 30 Jam (Total 180 Jam)",
        ]);

        // =====================================================
        // 1900: KOMUNIKASI TIM PERIKATAN
        // =====================================================
        $seedAnswers('1900', [
            'work_description'         => 'Audit Umum atas Laporan Keuangan Konsolidasian',
            'report_output'            => 'Laporan Auditor Independen atas Laporan Keuangan PT Indo American Seafoods Tbk dan Entitas Anak Untuk Tahun yang Berakhir 31 Desember 2024',
            'risk_assessment_approach' => "1. Pra Penugasan: Evaluasi penerimaan penugasan dan independensi tim.\n2. Perencanaan Audit: Buat strategi audit menyeluruh, materiality, dan alokasi jam kerja.\n3. Prosedur Penilaian Risiko: Identifikasi risiko salah saji material pada tingkat LK dan asersi.",
            'risk_response_approach'   => "1. Rancang tanggapan menyeluruh dan prosedur audit lanjutan (ToC & Substantive).\n2. Implementasikan pengujian detail akun-akun signifikan (Kas, Piutang, Persediaan, Aset Biologis, Pendapatan).\n3. Turunkan risiko audit ke tingkat rendah yang dapat diterima.",
            'reporting_approach'       => "1. Evaluasi kecukupan bukti audit yang diperoleh.\n2. Tentukan prosedur tambahan jika ditemukan salah saji di atas MUD.\n3. Rumuskan opini audit independen berdasarkan temuan audit.",
            'asset_liability_risks'    => "Pengakuan, Pengukuran, dan Klasifikasi:\n- Kas dan Bank: Rekonsiliasi & konfirmasi saldo bank\n- Piutang Usaha: CKPN & konfirmasi sampling\n- Persediaan & Aset Biologis: Stock opname & PSAK 69\n- Aset Tetap: Revaluasi tanah & penambahan mesin\n- Utang Bank & Usaha: Konfirmasi & cut-off testing",
            'income_expense_risks'     => "Pengakuan, Pengukuran, dan Klasifikasi:\n- Pendapatan Usaha: Pengakuan ekspor, PEB, cut-off\n- Beban Pokok Pendapatan: Alokasi biaya produksi pakan & benur\n- Beban Operasional: Operating expenses & freight logistik ekspor\n- Beban Keuangan: Bunga pinjaman bank modal kerja",
            'schedule_detail'          => 'Pelaksanaan audit lapangan: 2 Desember - 6 Desember 2024, jam kerja 08.30 – 17.30 WIB, bertempat di Jl. Ir. Sutami Km. 13 Desa Sukanegara, Kec. Tanjung Bintang, Kab. Lampung Selatan.',
            'team_members'             => "1. Kenny Nurardi Wijaya : Manager\n2. Netty Lidya Simanjuntak : Senior Associate\n3. Raynaldi Tribuana : Senior Associate\n4. Daniel Gerald H : Junior Associate\n5. Irene Rizka Amalia : Junior Associate\n6. Aryn Sasikirana : Junior Associate\n7. Kurotun Ainiyah : Junior Associate",
        ]);

        $this->command?->info('Fase1000AnswersSeeder: Berhasil mengisi data template riil untuk seluruh 23 form Fase 1000 (IAS-2024)!');
    }
}
