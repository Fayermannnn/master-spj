<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master data GLOBAL (§20 master prompt): daftar placeholder yang
 * "dikenal" sistem, dipakai memvalidasi hasil scan placeholder pada
 * DocumentTemplate (lihat PROJECT_DECISIONS.md D-016).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_variables', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('data_type');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_variables');
    }
};
