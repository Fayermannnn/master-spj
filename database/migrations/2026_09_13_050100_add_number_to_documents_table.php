<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrasi ADDITIVE (RULE 7) — nomor surat resmi hasil
 * `NumberingService::nextNumber()`, diisi otomatis SAAT generate kalau
 * template mendeteksi placeholder `document.number` DAN organisasi
 * project punya `NumberingSetting` aktif. Nullable — dokumen lama/
 * organisasi tanpa penomoran dikonfigurasi tetap valid tanpa nomor
 * (lihat PROJECT_DECISIONS.md D-029).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->string('number')->nullable()->after('deliverable_id');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropColumn('number');
        });
    }
};
