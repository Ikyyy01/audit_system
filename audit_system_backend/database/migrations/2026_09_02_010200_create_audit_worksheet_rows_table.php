<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Baris worksheet — level RESPONSE (jumlahnya dinamis per klien/engagement,
// beda dari audit_form_fields yang jumlahnya tetap per template). Nempel ke
// audit_form_responses yang sama, jadi workflow submit/review/approve/export
// yang udah ada buat form checklist otomatis kepakai juga di sini.
// Data per baris disimpen sebagai JSON {column_key: value} — bukan tabel cell
// terpisah, karena ini dokumen kerja yang dibaca ulang, bukan data yang perlu
// di-query per kolom lintas ribuan baris.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_worksheet_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('response_id')->constrained('audit_form_responses')->cascadeOnDelete();
            $table->unsignedInteger('row_order')->default(0);
            $table->enum('row_type', ['data', 'subtotal', 'total'])->default('data');
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_worksheet_rows');
    }
};
