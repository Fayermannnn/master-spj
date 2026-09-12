<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignUlid('document_requirement_id')->constrained('document_requirements')->cascadeOnDelete();
            $table->foreignUlid('document_template_id')->constrained('document_templates')->restrictOnDelete();
            $table->foreignUlid('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->unsignedInteger('version');
            $table->string('name');
            $table->json('data_snapshot');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('pdf_disk')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('pdf_original_filename')->nullable();
            $table->unsignedBigInteger('pdf_size')->nullable();
            $table->foreignUlid('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['project_id', 'document_requirement_id', 'version']);
            $table->index(['project_id', 'document_requirement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
