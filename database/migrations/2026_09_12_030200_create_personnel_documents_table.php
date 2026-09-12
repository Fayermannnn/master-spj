<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel_documents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('personnel_id')->constrained('personnel')->cascadeOnDelete();
            $table->string('document_type');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->foreignUlid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['personnel_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_documents');
    }
};
