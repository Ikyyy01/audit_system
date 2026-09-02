<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Tambah 'repeater' ke enum field_type: field yang isinya daftar baris berulang
// dengan kolom terstruktur (mis. "Name of Shareholders": Nama | % | Lembar | Nilai),
// gantinya user ngetik manual "1. Nama: 69% (...)" di satu textarea panjang.
// Raw SQL (bukan Blueprint::change()) supaya nggak butuh doctrine/dbal.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE audit_form_fields MODIFY field_type ENUM('text','number','date','dropdown','checkbox','textarea','file','currency','percentage','repeater') DEFAULT 'text'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE audit_form_fields MODIFY field_type ENUM('text','number','date','dropdown','checkbox','textarea','file','currency','percentage') DEFAULT 'text'");
    }
};
