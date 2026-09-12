<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tidak punya organization_id sendiri — scoping tenant diturunkan dari
 * project_id (lihat PROJECT_DECISIONS.md D-014).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('termin_number');
            $table->string('name');
            $table->decimal('percentage', 5, 2)->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('target_date')->nullable();
            $table->text('trigger')->nullable();
            $table->text('required_items')->nullable();
            $table->string('status');
            $table->date('submission_date')->nullable();
            $table->date('approval_date')->nullable();
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['project_id', 'termin_number']);
            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
