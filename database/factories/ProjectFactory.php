<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\ProjectManagement\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-6 months', 'now');
        $durationDays = fake()->numberBetween(60, 180);

        return [
            'organization_id' => Organization::factory(),
            'project_type_id' => ProjectType::factory(),
            'client_id' => Client::factory(),
            'ppk_contact_id' => null,
            'code' => Str::upper(Str::random(8)),
            'name' => 'Jasa Konsultansi '.fake()->words(3, true),
            'unit_work' => null,
            'project_manager_name' => fake()->name(),
            'start_date' => $startDate,
            'end_date' => (clone $startDate)->modify("+{$durationDays} days"),
            'duration_days' => $durationDays,
            'status' => ProjectStatus::Draft->value,
            'description' => fake()->paragraph(),
            'notes' => null,
            'created_by' => null,
        ];
    }
}
