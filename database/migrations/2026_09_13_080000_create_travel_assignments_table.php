<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perjalanan dinas seorang Personnel dalam rangka satu Project — dasar
 * pengisian otomatis Surat Perjalanan Dinas (SPPD, PROJECT_DECISIONS.md
 * D-034). Satu personil bisa punya BANYAK perjalanan dalam satu
 * project (tidak unique) — beda dari `personnel_assignments` yang
 * satu baris per (project, personnel).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_assignments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('personnel_id')->constrained('personnel')->restrictOnDelete();
            $table->string('destination');
            $table->text('purpose');
            $table->date('departure_date');
            $table->date('return_date');
            $table->string('transportation_mode')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'personnel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_assignments');
    }
};
