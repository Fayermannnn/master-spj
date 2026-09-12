<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Document Requirement Engine (§17-18 master prompt). Master data GLOBAL
 * (dikelola super_admin) — project_type_id nullable berarti requirement
 * berlaku untuk SEMUA jenis project (mis. Kontrak, SPMK), sedangkan yang
 * diisi hanya berlaku untuk jenis project tertentu. Lihat
 * PROJECT_DECISIONS.md D-015.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_requirements', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_type_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_type_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_requirements');
    }
};
