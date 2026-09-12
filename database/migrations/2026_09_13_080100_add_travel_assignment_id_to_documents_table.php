<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrasi ADDITIVE (RULE 7) — menghubungkan Document (Surat Perjalanan
 * Dinas) ke TravelAssignment yang dipilih saat generate, pola SAMA
 * dengan payment_id/deliverable_id/cost_item_id (D-027/D-033).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->foreignUlid('travel_assignment_id')->nullable()->after('cost_item_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('travel_assignment_id');
        });
    }
};
