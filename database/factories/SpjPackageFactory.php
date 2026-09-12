<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Spj\Enums\SpjPackageStatus;
use App\Models\Project;
use App\Models\SpjPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpjPackage>
 */
class SpjPackageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'payment_id' => null,
            'name' => 'Paket SPJ '.fake()->words(2, true),
            'status' => SpjPackageStatus::Draft->value,
            'notes' => null,
            'created_by' => null,
            'finalized_at' => null,
        ];
    }
}
