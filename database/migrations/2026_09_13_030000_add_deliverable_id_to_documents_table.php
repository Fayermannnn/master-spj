<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrasi ADDITIVE (RULE 7) — menghubungkan Document ke Deliverable
 * asli (opsional) alih-alih `deliverable.name` selalu diisi teks bebas
 * saat generate (lihat PROJECT_DECISIONS.md D-027). Nullable dan
 * `nullOnDelete()` supaya dokumen yang sudah digenerate TIDAK PERNAH
 * hilang/berubah kalau Deliverable yang jadi acuannya kemudian dihapus
 * — `data_snapshot` (termasuk `deliverable.name`) tetap sumber
 * kebenaran untuk dokumen yang SUDAH jadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->foreignUlid('deliverable_id')->nullable()->after('payment_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('deliverable_id');
        });
    }
};
