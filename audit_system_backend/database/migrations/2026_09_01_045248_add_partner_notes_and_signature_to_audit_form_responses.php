<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_form_responses', function (Blueprint $table) {
            $table->text('partner_notes')->nullable()->after('status');
            $table->enum('engagement_decision', ['Diterima', 'Ditolak'])->nullable()->after('partner_notes');
            $table->string('signature_path', 500)->nullable()->after('engagement_decision');
            $table->timestamp('signature_uploaded_at')->nullable()->after('signature_path');
        });
    }

    public function down(): void
    {
        Schema::table('audit_form_responses', function (Blueprint $table) {
            $table->dropColumn(['partner_notes', 'engagement_decision', 'signature_path', 'signature_uploaded_at']);
        });
    }
};
