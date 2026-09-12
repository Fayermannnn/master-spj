<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('personnel_category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('position')->nullable();
            $table->string('education')->nullable();
            $table->text('expertise')->nullable();
            $table->string('id_number')->nullable();
            $table->string('npwp')->nullable();
            $table->string('certificate_number')->nullable();
            $table->date('certificate_expiry_date')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->decimal('default_rate', 15, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel');
    }
};
