<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('project_type_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('client_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('ppk_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('unit_work')->nullable();
            $table->string('project_manager_name')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('duration_days')->nullable();
            $table->string('status');
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
