<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menghubungkan Payment (termin) ke CostItem yang dibayar — entitas ini
 * sudah disebut di blueprint §8 ("payment_items") sejak Phase 0 tapi
 * belum pernah dibuat (lihat PROJECT_DECISIONS.md D-025). Tanpa restrict
 * on delete, menghapus CostItem yang sudah dialokasikan akan diam-diam
 * merusak angka realisasi anggaran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('cost_item_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['payment_id', 'cost_item_id']);
            $table->index('cost_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_items');
    }
};
