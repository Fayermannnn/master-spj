<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\DocumentRequirement\Enums\ChecklistStatus;
use App\Models\DocumentRequirement;
use App\Models\Project;
use App\Models\ProjectChecklistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectChecklistItem>
 */
class ProjectChecklistItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'document_requirement_id' => DocumentRequirement::factory(),
            'status' => ChecklistStatus::Missing->value,
            'notes' => null,
        ];
    }
}
