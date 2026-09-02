<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// `form_type` yang udah ada (parent/child/single) itu soal HIERARKI form
// (1000 itu parent, 1100 itu child-nya). Kolom baru ini soal BENTUK isiannya:
// - checklist = daftar pertanyaan tetap (Form 1100, 1110, 1130 dst) -> DynamicForm.vue
// - worksheet = tabel dengan baris dinamis (Form 3100 Balance Sheet dst) -> WorksheetForm.vue
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_forms', function (Blueprint $table) {
            $table->enum('render_type', ['checklist', 'worksheet'])->default('checklist')->after('form_type');
        });
    }

    public function down(): void
    {
        Schema::table('audit_forms', function (Blueprint $table) {
            $table->dropColumn('render_type');
        });
    }
};
