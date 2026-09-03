<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Tambah 'time_range' ke enum field_type:
// field yang isinya rentang waktu dari jam-ke jam, misalnya "09.00 – 12.00".
// Dirender di frontend sebagai dua input time (jam digital) berdampingan.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE audit_form_fields MODIFY field_type ENUM('text','number','date','time_range','dropdown','checkbox','textarea','file','currency','percentage','repeater') DEFAULT 'text'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE audit_form_fields MODIFY field_type ENUM('text','number','date','dropdown','checkbox','textarea','file','currency','percentage','repeater') DEFAULT 'text'");
    }
};
