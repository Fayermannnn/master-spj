<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Status checklist SPJ per project. Baris dibuat saat requirement pertama
 * kali diketahui berlaku untuk project tsb (lihat ChecklistService) —
 * bukan dihitung ulang dari nol setiap tampil, supaya status
 * (fulfilled/dsb.) tidak hilang begitu rule/requirement berubah di
 * kemudian hari. Fase mendatang (Document Generator/SPJ Package) akan
 * mengisi status ini otomatis; untuk Phase 5 masih toggle manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_checklist_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('document_requirement_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'document_requirement_id']);
            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_checklist_items');
    }
};
