<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Evidence;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evidence>
 */
class EvidenceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'payment_id' => null,
            'personnel_id' => null,
            'document_requirement_id' => null,
            'name' => fake()->words(3, true),
            'category' => null,
            'description' => null,
            'disk' => 'local',
            'path' => 'evidences/'.fake()->uuid().'.jpg',
            'original_filename' => 'bukti.jpg',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(10_000, 500_000),
            'uploaded_by' => null,
            'notes' => null,
        ];
    }
}
