<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Personnel;
use App\Models\PersonnelAssignment;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonnelAssignment>
 */
class PersonnelAssignmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 4);
        $unitPrice = fake()->randomFloat(2, 5_000_000, 45_000_000);

        return [
            'project_id' => Project::factory(),
            'personnel_id' => Personnel::factory(),
            'role_on_project' => fake()->jobTitle(),
            'quantity' => $quantity,
            'unit' => fake()->randomElement(['OB', 'OH', 'OM', 'LS']),
            'unit_price' => $unitPrice,
            'subtotal' => round($quantity * $unitPrice, 2),
            'start_date' => null,
            'end_date' => null,
            'notes' => null,
        ];
    }
}
