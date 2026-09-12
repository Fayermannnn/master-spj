<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliverables', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignUlid('milestone_id')->nullable()->constrained('milestones')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('target_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'milestone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliverables');
    }
};
