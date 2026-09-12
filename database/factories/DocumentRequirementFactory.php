<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DocumentRequirement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DocumentRequirement>
 */
class DocumentRequirementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'project_type_id' => null,
            'code' => Str::upper(Str::slug($name, '_')),
            'name' => ucwords($name),
            'category' => null,
            'description' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
