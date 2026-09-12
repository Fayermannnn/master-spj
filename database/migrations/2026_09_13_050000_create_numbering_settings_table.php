<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu baris konfigurasi penomoran dokumen resmi PER ORGANISASI —
 * bukan per jenis dokumen, SENGAJA disederhanakan (RULE 67) sesuai
 * kelaziman "nomor surat keluar" satu organisasi (satu penomoran
 * berurutan untuk semua surat, terlepas jenisnya). Lihat
 * PROJECT_DECISIONS.md D-029.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('numbering_settings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('format_template');
            $table->unsignedInteger('next_sequence')->default(1);
            $table->string('reset_period')->default('yearly');
            $table->string('last_reset_period_key')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('numbering_settings');
    }
};
