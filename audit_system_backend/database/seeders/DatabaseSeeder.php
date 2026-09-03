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
            Fase1000FieldsSeeder::class,
            Fase1000SisaFieldsSeeder::class,
            Fase1000AnswersSeeder::class,
            // Fix seeder khusus 1130A/1130B — HARUS jalan setelah Fase1000AnswersSeeder,
            // karena mengubah field_type jadi 'repeater' + timpa jawaban ke format JSON
            // terstruktur (tabel dinamis dgn formula) menggantikan versi textarea lama.
            // Sebelumnya cuma bisa dijalanin manual satu-satu dan gampang kelewat kalau
            // ada yang migrate:fresh --seed ulang — sekarang otomatis ikut.
            Form1130AFixSeeder::class,
            Form1130BRepeaterFixSeeder::class,
            Form1130BStockholdersFixSeeder::class,
        ]);
    }
}
