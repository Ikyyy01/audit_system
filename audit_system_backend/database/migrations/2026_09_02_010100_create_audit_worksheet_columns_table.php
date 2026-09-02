<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Definisi KOLOM worksheet — level TEMPLATE (satu set kolom berlaku untuk
// semua response dari form yang sama). Contoh Form 3100 Balance Sheet:
// Nama Akun | Saldo Awal | Debit | Kredit | Saldo Akhir | Adjustment | dst.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_worksheet_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('audit_forms')->cascadeOnDelete();
            $table->string('column_key', 100); // dipakai sebagai key di JSON data baris, mis. 'saldo_awal'
            $table->string('column_label');
            $table->enum('data_type', ['text', 'number', 'currency', 'formula'])->default('text');
            $table->unsignedInteger('column_order')->default(0);
            $table->boolean('is_formula')->default(false);
            // Formula sederhana berbasis column_key lain, mis. "saldo_awal + debit - kredit".
            // Dievaluasi di frontend (dan divalidasi ulang di backend saat save) — bukan SQL.
            $table->string('formula_expression', 255)->nullable();
            $table->timestamps();

            $table->unique(['form_id', 'column_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_worksheet_columns');
    }
};
