<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('client_type', 50)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('engagements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('engagement_code', 100)->unique();
            $table->year('engagement_year');
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->timestamps();
        });

        Schema::create('audit_forms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->foreignId('parent_form_id')->nullable()->constrained('audit_forms')->nullOnDelete();
            $table->enum('form_type', ['parent', 'child', 'single'])->default('single');
            $table->timestamps();
        });

        Schema::create('audit_form_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('audit_forms')->cascadeOnDelete();
            $table->string('section_name');
            $table->unsignedInteger('section_order')->default(0);
            $table->timestamps();
        });

        Schema::create('audit_form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('audit_form_sections')->cascadeOnDelete();
            $table->string('field_name');
            $table->string('field_label');
            $table->enum('field_type', ['text', 'number', 'date', 'dropdown', 'checkbox', 'textarea', 'file', 'currency', 'percentage'])->default('text');
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('field_order')->default(0);
            $table->json('options_json')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('engagement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();
            $table->unique(['engagement_id', 'user_id', 'role_id']);
        });

        Schema::create('audit_form_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('audit_forms')->cascadeOnDelete();
            $table->foreignId('engagement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['draft', 'pending_review', 'reviewed', 'revision_required', 'approved'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_form_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('response_id')->constrained('audit_form_responses')->cascadeOnDelete();
            $table->foreignId('field_id')->constrained('audit_form_fields')->cascadeOnDelete();
            $table->longText('response_value')->nullable();
            $table->timestamps();
            $table->unique(['response_id', 'field_id']);
        });

        Schema::create('audit_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('response_id')->constrained('audit_form_responses')->cascadeOnDelete();
            $table->foreignId('reviewed_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('review_status', ['pending', 'approved', 'revision_required'])->default('pending');
            $table->text('comments')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('response_id')->constrained('audit_form_responses')->cascadeOnDelete();
            $table->foreignId('approved_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('comments')->nullable();
            $table->timestamp('approval_date')->nullable();
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('engagement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_id')->nullable()->constrained('audit_forms')->nullOnDelete();
            $table->string('document_name');
            $table->string('file_path', 500);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('file_path', 500);
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['document_id', 'version_number']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id');
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('audit_approvals');
        Schema::dropIfExists('audit_reviews');
        Schema::dropIfExists('audit_form_answers');
        Schema::dropIfExists('audit_form_responses');
        Schema::dropIfExists('audit_assignments');
        Schema::dropIfExists('audit_form_fields');
        Schema::dropIfExists('audit_form_sections');
        Schema::dropIfExists('audit_forms');
        Schema::dropIfExists('engagements');
        Schema::dropIfExists('clients');
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('role_id'));
        Schema::dropIfExists('roles');
    }
};
