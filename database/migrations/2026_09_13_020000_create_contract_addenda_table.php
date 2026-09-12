<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat amandemen/adendum kontrak — Contract sebelumnya hanya
 * mencerminkan nilai TERKINI, tanpa jejak perubahan (lihat
 * PROJECT_DECISIONS.md D-026). `previous_value`/`new_value` adalah
 * snapshot, bukan sumber kebenaran yang dihitung ulang — nilai
 * kontrak efektif tetap `contracts.contract_value`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_addenda', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('contract_id')->constrained()->cascadeOnDelete();
            $table->string('addendum_number')->nullable();
            $table->date('addendum_date');
            $table->text('reason');
            $table->decimal('previous_value', 15, 2)->nullable();
            $table->decimal('new_value', 15, 2)->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['contract_id', 'addendum_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_addenda');
    }
};
