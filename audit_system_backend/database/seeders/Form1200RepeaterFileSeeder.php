<?php

namespace Database\Seeders;

use App\Models\AuditForm;
use App\Models\AuditFormField;
use App\Models\AuditFormSection;
use Illuminate\Database\Seeder;

class Form1200RepeaterFileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Mengubah form 1200 dari 2 textarea generik menjadi:
     * - repeater untuk daftar tim
     * - file upload untuk document bukti
     */
    public function run(): void
    {
        $form1200 = AuditForm::where('code', '1200')->first();
        if (!$form1200) {
            $this->command?->warn('Form 1200 tidak ditemukan.');
            return;
        }

        $section = AuditFormSection::where('form_id', $form1200->id)
            ->where('section_name', 'Pernyataan Konfirmasi Independensi')
            ->first();

        if (!$section) {
            $this->command?->warn('Section Form 1200 tidak ditemukan.');
            return;
        }

        // Hapus field lama (textarea biasa)
        AuditFormField::where('section_id', $section->id)->delete();

        // 1. Checkbox pernyataan
        AuditFormField::create([
            'section_id' => $section->id,
            'field_name' => 'confirmation_statement',
            'field_label' => 'Saya mengkonfirmasi bahwa (i) saya/suami/istri/tanggungan/anak-anak/saudara kandung tidak memiliki kepentingan keuangan pada klien ini, dan (ii) independensi saya dapat dipertanggungjawabkan dalam hal hubungan pribadi dan konflik kepentingan',
            'field_type' => 'checkbox',
            'is_required' => true,
            'field_order' => 1,
        ]);

        // 2. Repeater Tabel Tim — tanda tangan per anggota ada di dalam tabel
        AuditFormField::create([
            'section_id' => $section->id,
            'field_name' => 'team_declarations',
            'field_label' => 'Daftar Anggota Tim Perikatan Audit',
            'field_type' => 'repeater',
            'is_required' => true,
            'field_order' => 2,
            'options_json' => [
                ['key' => 'no', 'label' => 'No', 'type' => 'text', 'width' => '50px'],
                ['key' => 'anggota_perikatan', 'label' => 'Anggota Perikatan (Nama Lengkap)', 'type' => 'text', 'width' => '35%'],
                ['key' => 'jabatan', 'label' => 'Jabatan (Posisi)', 'type' => 'text', 'width' => '25%'],
                ['key' => 'tanggal_konfirmasi', 'label' => 'Tanggal Konfirmasi', 'type' => 'date', 'width' => '15%'],
                ['key' => 'paraf', 'label' => 'Paraf / Tanda Tangan', 'type' => 'file', 'width' => '20%'],
            ],
        ]);

        $this->command?->info('Form 1200: struktur field telah diubah ke repeater (tanda tangan per anggota di dalam tabel) & checkbox!');
    }
}
