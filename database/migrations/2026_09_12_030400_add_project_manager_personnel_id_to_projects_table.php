<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambah referensi opsional ke Personnel untuk Ketua Tim/PM, mendampingi
 * (bukan menggantikan) `project_manager_name` — lihat PROJECT_DECISIONS.md
 * D-011. `project_manager_name` tetap dipakai untuk PM yang belum tercatat
 * sebagai Personnel di roster organisasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->foreignUlid('project_manager_personnel_id')
                ->nullable()
                ->after('project_manager_name')
                ->constrained('personnel')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('project_manager_personnel_id');
        });
    }
};
