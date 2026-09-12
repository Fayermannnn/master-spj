<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Template diikat ke DocumentRequirement (bukan langsung ke ProjectType)
 * — itu yang benar-benar dipakai saat generate dokumen di Phase 7. Lihat
 * PROJECT_DECISIONS.md D-016.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('document_requirement_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('version');
            $table->string('status');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->json('detected_variables')->nullable();
            $table->text('description')->nullable();
            $table->foreignUlid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['document_requirement_id', 'version']);
            $table->index(['document_requirement_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_templates');
    }
};
