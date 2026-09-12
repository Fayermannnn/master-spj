<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrasi ADDITIVE (RULE 7) — logo organisasi untuk kop surat otomatis
 * (PROJECT_DECISIONS.md D-030). Pola kolom SAMA seperti file lain di
 * app ini (DocumentTemplate/Evidence/PersonnelDocument): disk/path/
 * original_filename/mime_type/size — tapi di-embed langsung di
 * `organizations` (bukan tabel terpisah) karena hanya SATU logo per
 * organisasi, bukan one-to-many.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->string('logo_disk')->nullable()->after('is_active');
            $table->string('logo_path')->nullable()->after('logo_disk');
            $table->string('logo_original_filename')->nullable()->after('logo_path');
            $table->string('logo_mime_type')->nullable()->after('logo_original_filename');
            $table->unsignedInteger('logo_size')->nullable()->after('logo_mime_type');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn(['logo_disk', 'logo_path', 'logo_original_filename', 'logo_mime_type', 'logo_size']);
        });
    }
};
