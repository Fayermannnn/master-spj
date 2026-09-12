<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spj_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('spj_package_id')->constrained('spj_packages')->cascadeOnDelete();
            $table->foreignUlid('document_id')->nullable()->constrained('documents')->cascadeOnDelete();
            $table->foreignUlid('evidence_id')->nullable()->constrained('evidences')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['spj_package_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spj_items');
    }
};
