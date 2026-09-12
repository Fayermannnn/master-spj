<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Workplan\Enums\WorkplanStatus;
use App\Models\Deliverable;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deliverable>
 */
class DeliverableFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'milestone_id' => null,
            'name' => fake()->sentence(3),
            'description' => null,
            'target_date' => fake()->dateTimeBetween('now', '+3 months'),
            'completed_date' => null,
            'status' => WorkplanStatus::Pending->value,
            'sort_order' => 0,
            'created_by' => null,
            'notes' => null,
        ];
    }
}
