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
        ]);
    }
}
