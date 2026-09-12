<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master data GLOBAL (§59 master prompt) — administrator mengonfigurasi
 * jenis & tarif pajak sendiri, sistem tidak meng-hardcode satu jenis
 * pajak. Lihat PROJECT_DECISIONS.md D-013.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_types', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->decimal('rate', 5, 2);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_types');
    }
};
