<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// Seeder terpisah, aman dijalankan berkali-kali (idempotent).
// Jalankan: php artisan db:seed --class=Form3100ColumnsSeeder
//
// Ini kolom-kolom TEMPLATE buat Form 3100 (Balance Sheet) — kertas kerja
// neraca standar: saldo awal -> mutasi debit/kredit -> saldo akhir buku klien
// -> reklasifikasi/AJE -> saldo akhir audited. Baris-barisnya (nama akun per
// akun) diisi user sendiri per klien lewat WorksheetForm.vue, bukan di-seed
// di sini karena jumlah & nama akun beda-beda tiap klien.
class Form3100ColumnsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $formId = DB::table('audit_forms')->where('code', '3100')->value('id');
        if (! $formId) {
            $this->command?->error('Form dengan code 3100 belum ada di audit_forms. Jalankan AuditSystemSeeder dulu.');

            return;
        }

        // Tandai form ini sebagai worksheet, bukan checklist
        DB::table('audit_forms')->where('id', $formId)->update(['render_type' => 'worksheet']);

        // Bersihin dulu biar seeder ini aman dijalankan berkali-kali
        DB::table('audit_worksheet_columns')->where('form_id', $formId)->delete();

        $columns = [
            ['key' => 'account_ref', 'label' => 'No. Akun', 'type' => 'text', 'formula' => null],
            ['key' => 'account_name', 'label' => 'Nama Akun', 'type' => 'text', 'formula' => null],
            ['key' => 'saldo_awal', 'label' => 'Saldo Awal (Audited Tahun Lalu)', 'type' => 'currency', 'formula' => null],
            ['key' => 'debit', 'label' => 'Mutasi Debit', 'type' => 'currency', 'formula' => null],
            ['key' => 'kredit', 'label' => 'Mutasi Kredit', 'type' => 'currency', 'formula' => null],
            ['key' => 'saldo_akhir_buku', 'label' => 'Saldo Akhir (Buku Klien)', 'type' => 'formula', 'formula' => 'saldo_awal + debit - kredit'],
            ['key' => 'reklasifikasi', 'label' => 'Reklasifikasi / AJE', 'type' => 'currency', 'formula' => null],
            ['key' => 'saldo_akhir_audited', 'label' => 'Saldo Akhir (Audited)', 'type' => 'formula', 'formula' => 'saldo_akhir_buku + reklasifikasi'],
            ['key' => 'pr_ref', 'label' => 'Index KKA', 'type' => 'text', 'formula' => null],
            ['key' => 'catatan', 'label' => 'Catatan', 'type' => 'text', 'formula' => null],
        ];

        $order = 0;
        foreach ($columns as $c) {
            $order++;
            DB::table('audit_worksheet_columns')->insert([
                'form_id' => $formId,
                'column_key' => $c['key'],
                'column_label' => $c['label'],
                'data_type' => $c['type'],
                'column_order' => $order,
                'is_formula' => $c['formula'] !== null,
                'formula_expression' => $c['formula'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command?->info('Form 3100: '.count($columns).' kolom worksheet berhasil di-seed.');
    }
}
