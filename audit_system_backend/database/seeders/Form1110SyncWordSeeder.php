<?php

namespace Database\Seeders;

use App\Models\AuditForm;
use App\Models\AuditFormField;
use Illuminate\Database\Seeder;

class Form1110SyncWordSeeder extends Seeder
{
    /**
     * Sinkronkan struktur & kolom Form 1110 (Survey Klien) agar 100% PERSIS
     * dengan dokumen Word asli:
     * "1110 Survey Klien IAS 2024 Rev 1.docx"
     *
     * Catatan penting:
     * - Name of Shareholders HANYA ada Nama & % Kepemilikan (TIDAK ada jumlah lembar atau nilai nominal di Word).
     * - Attendants: Nama & Jabatan/Instansi.
     * - Name of Management: Jabatan & Nama.
     * - Total Assets: Komponen Aset & Nilai.
     * - Main Customers/Vendors: Nama Distributor / Vendor.
     */
    public function run(): void
    {
        $form = AuditForm::where('code', '1110')->first();
        if (!$form) {
            $this->command?->warn('Form 1110 tidak ditemukan.');
            return;
        }

        $section = $form->sections()->first();
        if (!$section) {
            return;
        }

        $fields = [
            [
                'name'  => 'date_of_survey',
                'label' => 'Date of Survey',
                'type'  => 'date',
                'cols'  => null,
            ],
            [
                'name'  => 'venue',
                'label' => 'Venue',
                'type'  => 'text',
                'cols'  => null,
            ],
            [
                'name'  => 'survey_time',
                'label' => 'Time (From - To)',
                'type'  => 'time_range',
                'cols'  => null,
            ],
            [
                'name'  => 'attendants',
                'label' => 'Attendants',
                'type'  => 'repeater',
                'cols'  => [
                    ['key' => 'nama', 'label' => 'Nama Peserta', 'type' => 'text', 'width' => '50%'],
                    ['key' => 'jabatan', 'label' => 'Jabatan / Instansi', 'type' => 'text', 'width' => '50%'],
                ],
            ],
            [
                'name'  => 'legal_name',
                'label' => 'Legal Name',
                'type'  => 'text',
                'cols'  => null,
            ],
            [
                'name'  => 'scope_of_engagement',
                'label' => 'Scope of the Engagement',
                'type'  => 'text',
                'cols'  => null,
            ],
            [
                'name'  => 'financial_accounting_standard',
                'label' => 'Financial Accounting Standard',
                'type'  => 'text',
                'cols'  => null,
            ],
            [
                'name'  => 'deliverables',
                'label' => 'Deliverables',
                'type'  => 'text',
                'cols'  => null,
            ],
            [
                'name'  => 'objectives',
                'label' => 'Objectives',
                'type'  => 'textarea',
                'cols'  => null,
            ],
            [
                'name'  => 'name_of_shareholders',
                'label' => 'Name of Shareholders',
                'type'  => 'repeater',
                // SESUAI DOKUMEN ASLI: HANYA ADA NAMA DAN % KEPEMILIKAN
                'cols'  => [
                    ['key' => 'nama', 'label' => 'Nama Pemegang Saham', 'type' => 'text', 'width' => '70%'],
                    ['key' => 'persentase', 'label' => '% Kepemilikan', 'type' => 'text', 'width' => '30%'],
                ],
            ],
            [
                'name'  => 'name_of_management',
                'label' => "Name of the Company's Management (BOC and BOD)",
                'type'  => 'repeater',
                'cols'  => [
                    ['key' => 'jabatan', 'label' => 'Jabatan / Posisi', 'type' => 'text', 'width' => '40%'],
                    ['key' => 'nama', 'label' => 'Nama Pejabat', 'type' => 'text', 'width' => '60%'],
                ],
            ],
            [
                'name'  => 'ultimate_shareholder',
                'label' => 'Ultimate Shareholder',
                'type'  => 'textarea',
                'cols'  => null,
            ],
            [
                'name'  => 'business_activity',
                'label' => 'Business Activity of the Company',
                'type'  => 'textarea',
                'cols'  => null,
            ],
            [
                'name'  => 'reporting_currency',
                'label' => 'Reporting Currency',
                'type'  => 'text',
                'cols'  => null,
            ],
            [
                'name'  => 'total_assets_period',
                'label' => 'Total Assets as of (Periode / Tanggal Aset)',
                'type'  => 'text',
                'cols'  => null,
            ],
            [
                'name'  => 'total_assets',
                'label' => 'Komponen Total Assets',
                'type'  => 'repeater',
                'cols'  => [
                    ['key' => 'kategori', 'label' => 'Komponen Aset', 'type' => 'text', 'width' => '50%'],
                    ['key' => 'nilai', 'label' => 'Nilai (IDR)', 'type' => 'number', 'width' => '50%', 'total' => true, 'total_label' => 'Total Assets'],
                ],
            ],
            [
                'name'  => 'total_revenues',
                'label' => 'Total Revenues (Pendapatan)',
                'type'  => 'text',
                'cols'  => null,
            ],
            [
                'name'  => 'main_customers',
                'label' => 'Main Customers',
                'type'  => 'repeater',
                'cols'  => [
                    ['key' => 'nama', 'label' => 'Nama Customer', 'type' => 'text', 'width' => '100%'],
                ],
            ],
            [
                'name'  => 'main_distributors_vendors',
                'label' => 'Main Distributors / Vendors',
                'type'  => 'repeater',
                'cols'  => [
                    ['key' => 'nama', 'label' => 'Nama Distributor / Vendor', 'type' => 'text', 'width' => '100%'],
                ],
            ],
            [
                'name'  => 'accounting_system',
                'label' => 'Accounting System',
                'type'  => 'text',
                'cols'  => null,
            ],
            [
                'name'  => 'accounting_issues',
                'label' => 'Accounting Issues',
                'type'  => 'repeater',
                'cols'  => [
                    ['key' => 'topik', 'label' => 'Isu Akuntansi / Topik', 'type' => 'text', 'width' => '30%'],
                    ['key' => 'uraian', 'label' => 'Uraian / Penjelasan', 'type' => 'textarea', 'width' => '70%'],
                ],
            ],
            [
                'name'  => 'ethics',
                'label' => 'Ethics',
                'type'  => 'textarea',
                'cols'  => null,
            ],
            [
                'name'  => 'conclusion',
                'label' => 'Conclusion',
                'type'  => 'textarea',
                'cols'  => null,
            ],
        ];

        $order = 0;
        foreach ($fields as $f) {
            $order++;
            AuditFormField::updateOrCreate(
                ['section_id' => $section->id, 'field_name' => $f['name']],
                [
                    'field_label'  => $f['label'],
                    'field_type'   => $f['type'],
                    'is_required'  => true,
                    'field_order'  => $order,
                    'options_json' => $f['cols'],
                ]
            );
        }

        $this->command?->info('Form 1110: tabel repeater disesuaikan persis dengan isi file Word asli (tanpa kolom tambahan).');
    }
}
