<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SpjItem;
use App\Models\SpjPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpjItem>
 */
class SpjItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'spj_package_id' => SpjPackage::factory(),
            'document_id' => null,
            'evidence_id' => null,
            'sort_order' => 0,
        ];
    }
}
