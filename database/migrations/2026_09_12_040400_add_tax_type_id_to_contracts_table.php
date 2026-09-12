<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive (RULE 7 CLAUDE.md) — tax_amount/net_value Phase 2 TETAP bisa
 * diisi manual; tax_type_id hanya dipakai sebagai starting point/default
 * saat mengisi form, bukan sumber kebenaran yang dipaksakan. Lihat
 * PROJECT_DECISIONS.md D-013.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->foreignUlid('tax_type_id')
                ->nullable()
                ->after('contract_value')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tax_type_id');
        });
    }
};
