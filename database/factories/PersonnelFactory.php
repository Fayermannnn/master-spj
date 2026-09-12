<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Personnel;
use App\Models\PersonnelCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Personnel>
 */
class PersonnelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'personnel_category_id' => PersonnelCategory::factory(),
            'name' => fake()->name(),
            'position' => fake()->jobTitle(),
            'education' => 'S1',
            'expertise' => null,
            'id_number' => fake()->numerify('################'),
            'npwp' => fake()->numerify('##.###.###.#-###.###'),
            'certificate_number' => null,
            'certificate_expiry_date' => null,
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'default_rate' => fake()->randomFloat(2, 5_000_000, 50_000_000),
            'is_active' => true,
            'notes' => null,
        ];
    }
}
