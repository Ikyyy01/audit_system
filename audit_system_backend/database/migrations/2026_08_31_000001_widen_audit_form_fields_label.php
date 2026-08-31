<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// field_label sebelumnya VARCHAR(255) — terlalu pendek untuk pertanyaan checklist
// audit yang sering panjang (Form 1100 no. 2 saja sudah ~250 karakter tanpa sub-item).
// Pakai raw SQL (bukan Blueprint::change()) supaya tidak butuh package doctrine/dbal.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE audit_form_fields MODIFY field_label TEXT NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE audit_form_fields MODIFY field_label VARCHAR(255) NOT NULL');
    }
};
