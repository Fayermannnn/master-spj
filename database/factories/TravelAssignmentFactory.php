<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Personnel;
use App\Models\Project;
use App\Models\TravelAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TravelAssignment>
 */
class TravelAssignmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $departure = fake()->dateTimeBetween('+1 week', '+2 weeks');

        return [
            'project_id' => Project::factory(),
            'personnel_id' => Personnel::factory(),
            'destination' => fake()->city(),
            'purpose' => fake()->sentence(6),
            'departure_date' => $departure,
            'return_date' => (clone $departure)->modify('+3 days'),
            'transportation_mode' => fake()->randomElement(['Darat', 'Udara', 'Laut']),
            'notes' => null,
            'created_by' => null,
        ];
    }
}
