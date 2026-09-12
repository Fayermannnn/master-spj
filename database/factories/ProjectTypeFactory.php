<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProjectType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProjectType>
 */
class ProjectTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'code' => Str::upper(Str::slug($name, '_')),
            'name' => ucwords($name),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
