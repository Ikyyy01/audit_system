<?php

namespace Database\Seeders;

use App\Models\AuditForm;
use App\Models\AuditFormField;
use Illuminate\Database\Seeder;

class Form1110RepeaterFixSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Mengubah field textarea yang seharusnya berupa tabel di form 1110 menjadi repeater.
     */
    public function run(): void
    {
        $form1110 = AuditForm::where('code', '1110')->first();
        if (!$form1110) {
            $this->command?->warn('Form 1110 tidak ditemukan.');
            return;
        }

        $section = $form1110->sections()->first();
        if (!$section) {
            return;
        }

        // 1. Attendants (Daftar hadir)
        AuditFormField::where('section_id', $section->id)->where('field_name', 'attendants')->update([
            'field_type' => 'repeater',
            'options_json' => [
                ['key' => 'nama', 'label' => 'Nama Peserta', 'type' => 'text', 'width' => '50%'],
                ['key' => 'jabatan', 'label' => 'Jabatan / Instansi', 'type' => 'text', 'width' => '50%'],
            ],
        ]);

        // 2. Name of Shareholders (Pemegang saham)
        AuditFormField::where('section_id', $section->id)->where('field_name', 'name_of_shareholders')->update([
            'field_type' => 'repeater',
            'options_json' => [
                ['key' => 'nama', 'label' => 'Nama Pemegang Saham', 'type' => 'text', 'width' => '40%'],
                ['key' => 'persentase', 'label' => '% Kepemilikan', 'type' => 'text', 'width' => '15%'],
                ['key' => 'jumlah_lembar', 'label' => 'Jumlah Lembar', 'type' => 'text', 'width' => '20%'],
                ['key' => 'nilai_nominal', 'label' => 'Nilai Nominal', 'type' => 'text', 'width' => '25%'],
            ],
        ]);

        // 3. Name of Management (BOC and BOD)
        AuditFormField::where('section_id', $section->id)->where('field_name', 'name_of_management')->update([
            'field_type' => 'repeater',
            'options_json' => [
                ['key' => 'jabatan', 'label' => 'Jabatan / Posisi', 'type' => 'text', 'width' => '40%'],
                ['key' => 'nama', 'label' => 'Nama Lengkap', 'type' => 'text', 'width' => '60%'],
            ],
        ]);

        // 4. Total Assets
        AuditFormField::where('section_id', $section->id)->where('field_name', 'total_assets')->update([
            'field_type' => 'repeater',
            'options_json' => [
                ['key' => 'kategori', 'label' => 'Kategori (Current/Non-Current/Total)', 'type' => 'text', 'width' => '50%'],
                ['key' => 'nilai', 'label' => 'Nilai', 'type' => 'text', 'width' => '50%'],
            ],
        ]);

        // 5. Main Customers / Vendors
        AuditFormField::where('section_id', $section->id)->where('field_name', 'main_customers_vendors')->update([
            'field_type' => 'repeater',
            'options_json' => [
                ['key' => 'nama', 'label' => 'Nama Customer / Vendor', 'type' => 'text', 'width' => '40%'],
                ['key' => 'tipe', 'label' => 'Tipe (Customer / Vendor)', 'type' => 'text', 'width' => '20%'],
                ['key' => 'keterangan', 'label' => 'Keterangan Tambahan', 'type' => 'text', 'width' => '40%'],
            ],
        ]);

        $this->command?->info('Form 1110: 5 field berhasil diubah dari textarea menjadi repeater!');
    }
}
