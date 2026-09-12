<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrasi ADDITIVE (RULE 7) — menghubungkan Document (khususnya Slip
 * Gaji) ke CostItem personil yang dipilih saat generate, pola SAMA
 * dengan `payment_id`/`deliverable_id` (D-027). Nullable + `nullOnDelete()`
 * — dokumen yang sudah jadi tidak boleh berubah/hilang kalau CostItem
 * acuannya kemudian dihapus (D-016/D-018: dokumen final immutable).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->foreignUlid('cost_item_id')->nullable()->after('deliverable_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cost_item_id');
        });
    }
};
