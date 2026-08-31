<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// Seeder terpisah (bukan bagian dari AuditSystemSeeder) supaya bisa dijalankan
// sendiri tanpa bentrok unique constraint di roles/users/clients yang udah ke-seed.
// Jalankan: php artisan db:seed --class=Form1100FieldsSeeder
//
// Isinya = pertanyaan asli Form 1100 yang sama persis dengan yang di-hardcode
// di Form1100.vue, dipecah jadi 2 field per nomor (jawaban dropdown + komentar
// textarea) supaya bisa dirender generic oleh DynamicForm.vue. Dipakai lewat
// route /form/dynamic/1100 sebagai pembanding terhadap /form/1100 yang asli.
class Form1100FieldsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $formId = DB::table('audit_forms')->where('code', '1100')->value('id');
        if (! $formId) {
            $this->command?->error('Form dengan code 1100 belum ada di audit_forms. Jalankan AuditSystemSeeder dulu.');
            return;
        }

        // Bersihin dulu biar seeder ini aman dijalankan berkali-kali (idempotent)
        $existingSectionIds = DB::table('audit_form_sections')->where('form_id', $formId)->pluck('id');
        DB::table('audit_form_fields')->whereIn('section_id', $existingSectionIds)->delete();
        DB::table('audit_form_sections')->where('form_id', $formId)->delete();

        $answerOptions = json_encode([
            ['value' => 'Y', 'label' => 'Ya'],
            ['value' => 'T', 'label' => 'Tidak'],
            ['value' => 'NA', 'label' => 'N/A'],
        ]);

        $sections = [
            [
                'title' => 'Integritas dan Karakter Calon Klien',
                'questions' => [
                    ['no' => '1', 'text' => 'Apakah anda, klien anda, atau rekan kerja anda mengenal calon klien tersebut?', 'sub' => []],
                    ['no' => '2', 'text' => 'Apakah anda yakin bahwa tidak ada hal-hal atau kondisi yang mengakibatkan diragukannya pemilik, dewan pimpinan, atau manajemen calon klien? Khususnya, apakah anda mempunyai keyakinan memadai bahwa hal-hal di bawah ini tidak terjadi pada calon klien?', 'sub' => [
                        'a. Putusan sanksi hukum',
                        'b. Dugaan adanya tindakan illegal atau kecurangan',
                        'c. Investigasi yang sedang berjalan',
                        'd. Keanggotaan manajemen dalam organisasi profesional yang mempunyai reputasi kurang baik',
                        'e. Publikasi negatif, dan kedekatan dengan pihak yang etikanya dipertanyakan',
                    ]],
                ],
            ],
            [
                'title' => 'Auditor / Akuntan Terdahulu',
                'questions' => [
                    ['no' => '3', 'text' => 'Apakah anda telah menghubungi auditor/akuntan terdahulu (Jika relevan dalam yurisdiksi anda) dan menanyakan tentang:', 'sub' => [
                        'a. Akses terhadap kertas kerja calon klien',
                        'b. Adanya jasa profesional yang belum diselesaikan',
                        'c. Adanya perbedaan pendapat atau ketidaksepakatan',
                        'd. Integritas manajemen dan pimpinan',
                        'e. Alasan pergantian; dan',
                        'f. Adanya permintaan yang tidak masuk akal atau sikap tidak kooperatif',
                    ]],
                    ['no' => '4', 'text' => 'Apakah anda telah mendapat izin dari KAP terdahulu untuk menelaah kertas kerja tahun lalu (Jika diizinkan)? Jika ya, apakah anda telah melakukan penelaahan terhadap dokumentasi perencanaan periode lalu yang dilakukan oleh KAP terdahulu, dan menilai apakah KAP terdahulu:', 'sub' => [
                        'a. Dinyatakan independen terhadap klien',
                        'b. Dalam pelaksanaan audit, apakah KAP telah menerapkan standar sesuai SPM',
                        'c. Telah memiliki sumber daya dan keahlian yang memadai; dan',
                        'd. Telah memiliki pemahaman atas entitas dan lingkungannya',
                    ]],
                ],
            ],
            [
                'title' => 'Laporan Keuangan Sebelumnya',
                'questions' => [
                    ['no' => '5', 'text' => 'Apakah anda telah menerima dan menelaah salinan:', 'sub' => [
                        'a. Laporan keuangan untuk periode sekurang-kurangnya dua tahun terakhir',
                        'b. Berkas surat pemberitahuan dan ketetapan pajak yang terkait dalam dua tahun terakhir; dan',
                        'c. Surat rekomendasi kepada manajemen (management letter) dalam dua atau tiga tahun terakhir',
                    ]],
                ],
            ],
            [
                'title' => 'Penerimaan Klien & Saldo Awal',
                'questions' => [
                    ['no' => '6', 'text' => 'Seandainya anda mendapatkan akses, apakah anda telah menelaah kertas kerja periode sebelumnya yang dibuat oleh auditor atau akuntan terdahulu, yang bertujuan untuk:', 'sub' => [
                        'a. Menilai kewajaran saldo akhir periode sebelumnya dengan menitikberatkan akun-akun yang signifikan, untuk menentukan perlu tidaknya dilakukan penyajian kembali',
                        'b. Menentukan apakah auditor/akuntan terdahulu mengetahui adanya salah saji yang material',
                        'c. Menentukan dampak salah saji yang tidak material yang tidak disesuaikan pada laporan keuangan tahun sebelumnya; dan',
                        'd. Menilai kelayakan sistem akuntansi manajemen dengan menelaah jurnal penyesuaian dan surat rekomendasi kepada manajemen (management letter) yang dikeluarkan oleh auditor/akuntan terdahulu',
                    ]],
                    ['no' => '7', 'text' => 'Apakah anda telah menentukan kebijakan akuntansi yang signifikan dan metode yang digunakan dalam laporan keuangan periode tahun sebelumnya dan mempertimbangkan apakah telah diterapkan secara tepat dan konsisten? Misalnya:', 'sub' => [
                        'a. Penilaian signifikan seperti penyisihan piutang tak tertagih, persediaan, dan investasi',
                        'b. Kebijakan dan tarif amortisasi',
                        'c. Estimasi signifikan; dan',
                        'd. Lainnya (silakan mengidentifikasikan)',
                    ]],
                    ['no' => '8', 'text' => 'Apakah anda telah menentukan bahwa opini tidak menyatakan pendapat atas laporan keuangan tersebut akan dikeluarkan, sebagai akibat tidak diperolehnya keyakinan memadai pada saldo awal?', 'sub' => []],
                ],
            ],
            [
                'title' => 'Keahlian dan Sumber Daya Tim',
                'questions' => [
                    ['no' => '9', 'text' => 'Apakah anda telah memperoleh pemahaman menyeluruh tentang bisnis dan operasi klien? (Lengkapi pemahaman pada memorandum klien atau gunakan checklist standar sebagai sumber Informasi).', 'sub' => []],
                    ['no' => '10', 'text' => 'Apakah rekan dan staf memiliki pemahaman yang memadai mengenai praktik akuntansi atas industri calon klien untuk melaksanakan suatu perikatan? Jika tidak, apakah informasi yang dibutuhkan untuk memahami praktik akuntansi industry terkait telah diperoleh? Identifikasi sumber-sumber tersebut.', 'sub' => []],
                    ['no' => '11', 'text' => 'Apakah terdapat hal yang telah diidentifikasi yang memerlukan sebuah pengetahuan khusus? Jika ya, apakah pengetahuan yang dibutuhkan tersebut telah tersedia? Identifikasi sumber-sumber tersebut.', 'sub' => []],
                ],
            ],
            [
                'title' => 'Penilaian Independensi',
                'questions' => [
                    ['no' => '12', 'text' => 'Identifikasi dan dokumentasikan larangan-larangan yang ada (ancaman terhadap independensi dimana tidak terdapat pencegahan yang memadai) seperti:', 'sub' => [
                        'a. Penerimaan hadiah yang signifikan atau keramahtamahan dari klien',
                        'b. Hubungan bisnis yang dekat dengan klien',
                        'c. Hubungan keluarga dan kedekatan pribadi dengan klien',
                        'd. Fee yang jauh di bawah harga pasar',
                        'e. Kepentingan keuangan pada klien',
                        'f. Adanya hubungan ketenagakerjaan pada periode jasa assurance dengan klien',
                        'g. Pinjaman kepada/dari klien',
                        'h. Membuat jurnal atau klasifikasi akuntansi tanpa persetujuan manajemen',
                        'i. Melaksanakan fungsi manajemen untuk klien',
                        'j. Melaksanakan jasa non-assurance (konsultasi keuangan, bantuan hukum, jasa penilaian hal material)',
                    ]],
                    ['no' => '13', 'text' => 'Mengacu pada Bagian B Kode Etik sebagai panduan dalam mengidentifikasi ancaman dan tindak pengamanan terhadap independensi. Identifikasi dan dokumentasikan ancaman:', 'sub' => [
                        'a. Ancaman kepentingan pribadi',
                        'b. Ancaman telaah-pribadi',
                        'c. Ancaman kedekatan',
                        'd. Ancaman intimidasi',
                    ]],
                ],
            ],
            [
                'title' => 'Penelaahan Review Perikatan',
                'questions' => [
                    ['no' => '14', 'text' => 'Sudahkah anda menentukan bahwa resiko terkait dengan industri dan calon klien masih dapat diterima oleh KAP? Jelaskan review yang sudah diketahui dan diduga akan terjadi dan dampaknya terhadap perikatan, termasuk:', 'sub' => [
                        'a. Pemilik yang dominan', 'b. Pelanggaran peraturan perundangan denda/penalti material',
                        'c. Permasalahan pembiayaan atau ketidakmampuan menyelesaikan', 'd. Perhatian media yang tinggi terhadap entitas/manajemen',
                        'e. Trend dan kinerja industri', 'f. Manajemen yang terlalu konservatif atau terlalu optimis',
                        'g. Partisipasi dalam bisnis berisiko tinggi', 'h. Sistem akuntansi dan pencatatan yang buruk',
                        'i. Transaksi tidak biasa / hubungan istimewa signifikan', 'j. Struktur operasi rumit / tidak biasa',
                        'k. Pengendalian dan manajemen yang lemah', 'l. Lemahnya kebijakan pengakuan pendapatan',
                        'm. Pengaruh signifikan perubahan teknologi', 'n. Potensi manfaat manajemen tergantung kinerja keuangan',
                        'o. Isu kompetensi/kredibilitas manajemen', 'p. Perubahan terkini manajemen kunci/akuntan/pengacara',
                        'q. Kewajiban pelaporan entitas kepada publik',
                    ]],
                    ['no' => '15', 'text' => 'Siapa yang umumnya menggunakan laporan keuangan? (Perbankan, DJP, Regulator, Manajemen, Kreditur, Investor, Pemegang Saham). Apakah ada perselisihan di antara pemegang saham?', 'sub' => []],
                    ['no' => '16', 'text' => 'Adakah bagian-bagian tertentu dari laporan keuangan atau akun-akun tertentu yang perlu mendapat perhatian lebih? Jika ya, dokumentasikan rinciannya.', 'sub' => []],
                    ['no' => '17', 'text' => 'Apakah auditor/akuntan terdahulu mengajukan banyak jurnal penyesuaian dan atau mengidentifikasikan banyak koreksi yang tidak material dan tidak perlu disesuaikan?', 'sub' => []],
                    ['no' => '18', 'text' => 'Apakah anda yakin bahwa tidak ada keraguan yang signifikan terhadap kelangsungan usaha calon klien dalam waktu mendatang (sekurang-kurangnya satu tahun mendatang)?', 'sub' => []],
                    ['no' => '19', 'text' => 'Apakah anda yakin bahwa calon klien mau dan mampu membayar imbalan jasa profesional yang wajar?', 'sub' => []],
                ],
            ],
            [
                'title' => 'Pembatasan Ruang Lingkup & Lain-lain',
                'questions' => [
                    ['no' => '20', 'text' => 'Apakah anda yakin bahwa tidak ada pembatasan ruang lingkup oleh manajemen klien yang mempengaruhi pekerjaan anda?', 'sub' => []],
                    ['no' => '21', 'text' => 'Apakah terdapat kriteria yang sesuai untuk digunakan dalam mengevaluasi informasi hal pokok perikatan?', 'sub' => []],
                    ['no' => '22', 'text' => 'Apakah jangka waktu untuk menyelesaikan pekerjaan masuk akal?', 'sub' => []],
                    ['no' => '23', 'text' => 'Apakah terdapat hal-hal lain berkaitan dengan penerimaan klien yang perlu dipertimbangkan, seperti penelaahan secara lebih detail yang terkait dengan independensi dan faktor-faktor lainnya yang beresiko?', 'sub' => []],
                    ['no' => '24', 'text' => 'Catatan lainnya.', 'sub' => []],
                ],
            ],
        ];

        $sectionOrder = 0;
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
            foreach ($section['questions'] as $q) {
                $label = $q['no'] . '. ' . $q['text'];
                if (! empty($q['sub'])) {
                    $label .= "\n" . implode("\n", $q['sub']);
                }

                $fieldOrder++;
                DB::table('audit_form_fields')->insert([
                    'section_id' => $sectionId,
                    'field_name' => 'q' . $q['no'] . '_jawaban',
                    'field_label' => $label,
                    'field_type' => 'dropdown',
                    'is_required' => true,
                    'field_order' => $fieldOrder,
                    'options_json' => $answerOptions,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $fieldOrder++;
                DB::table('audit_form_fields')->insert([
                    'section_id' => $sectionId,
                    'field_name' => 'q' . $q['no'] . '_komentar',
                    'field_label' => 'Komentar / Penjelasan No. ' . $q['no'],
                    'field_type' => 'textarea',
                    'is_required' => false,
                    'field_order' => $fieldOrder,
                    'options_json' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $this->command?->info('Form 1100: ' . count($sections) . ' section, 24 pertanyaan (48 field) berhasil di-seed.');
    }
}
