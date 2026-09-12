<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrasi ADDITIVE (RULE 7) — mendukung status `Submitted` baru di
 * `SpjPackageStatus` (PROJECT_DECISIONS.md D-028). Riwayat LENGKAP
 * setiap submit/approve/reject SUDAH tercatat di `audit_logs` (setiap
 * aksi service memanggil `AuditLogService::record()`) — kolom di sini
 * HANYA mencerminkan status siklus TERKINI (kapan/oleh siapa diajukan,
 * kapan/oleh siapa direview, catatan review terakhir), bukan tabel
 * riwayat baru. Beda dengan ContractAddendum (D-026) yang butuh
 * snapshot nilai untuk ditampilkan sebagai daftar, di sini cukup satu
 * baris status terkini + Audit Log viewer yang sudah ada untuk jejak
 * lengkapnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spj_packages', function (Blueprint $table): void {
            $table->timestamp('submitted_at')->nullable()->after('notes');
            $table->foreignUlid('submitted_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('submitted_by');
            $table->foreignUlid('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('spj_packages', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['submitted_at', 'reviewed_at', 'review_notes']);
        });
    }
};
