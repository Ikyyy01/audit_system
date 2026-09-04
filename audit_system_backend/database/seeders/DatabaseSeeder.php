<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AuditSystemSeeder::class,
            Form1100FieldsSeeder::class,
            Form3100ColumnsSeeder::class,
            Fase1000FieldsSeeder::class,
            Fase1000SisaFieldsSeeder::class,
            Fase1000AnswersSeeder::class,
            // Fix seeder khusus 1130A/1130B — HARUS jalan setelah Fase1000AnswersSeeder,
            // karena mengubah field_type jadi 'repeater' + timpa jawaban ke format JSON
            // terstruktur (tabel dinamis dgn formula) menggantikan versi textarea lama.
            // Sebelumnya cuma bisa dijalanin manual satu-satu dan gampang kelewat kalau
            // ada yang migrate:fresh --seed ulang — sekarang otomatis ikut.
            //
            // Form1130BRepeaterFixSeeder (versi lama, kolom lebih sedikit dari Excel asli)
            // di-nonaktifkan (.deprecated) karena isinya konflik sama
            // Form1130BExcelSyncSeeder di bawah — dua-duanya nulis ke field yang sama
            // (directors_commissioners, subsidiaries, investments) dengan kolom beda,
            // jadi kalau dua-duanya jalan bakal rebutan & datanya gak nyambung ke kolom.
            // Form1130BExcelSyncSeeder yang menang karena paling akurat & lengkap
            // (dicocokkan langsung ke Excel sumber, termasuk Date of Birth & tanpa
            // kolom % yang memang tidak ada di Excel untuk subsidiaries).
            Form1130AFixSeeder::class,
            Form1130BExcelSyncSeeder::class,
            Form1130DEntitiesTreeSeeder::class,
            Form1200RepeaterFileSeeder::class,
            Form1110SyncWordSeeder::class,
            Form1400SyncWordSeeder::class,
        ]);
    }
}
