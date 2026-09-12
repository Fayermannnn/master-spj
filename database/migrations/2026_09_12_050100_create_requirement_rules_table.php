<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kondisi tambahan pada satu DocumentRequirement. Semua rule aktif milik
 * satu requirement dievaluasi dengan AND (§18 master prompt: rule engine
 * sederhana, bukan expression engine bebas — field/operator/value dari
 * kosakata tetap, lihat App\Domain\DocumentRequirement\Enums).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requirement_rules', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('document_requirement_id')->constrained()->cascadeOnDelete();
            $table->string('field');
            $table->string('operator');
            $table->string('value')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('document_requirement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requirement_rules');
    }
};
