<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CostCategory;
use App\Models\CostItem;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CostItem>
 */
class CostItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 10);
        $unitPrice = fake()->randomFloat(2, 100_000, 10_000_000);
        $subtotal = round($quantity * $unitPrice, 2);

        return [
            'project_id' => Project::factory(),
            'cost_category_id' => CostCategory::factory(),
            'personnel_assignment_id' => null,
            'tax_type_id' => null,
            'description' => fake()->sentence(4),
            'quantity' => $quantity,
            'unit' => fake()->randomElement(['Paket', 'Buah', 'Hari', 'Bulan']),
            'unit_price' => $unitPrice,
            'is_tax_inclusive' => false,
            'subtotal' => $subtotal,
            'tax_amount' => 0,
            'total' => $subtotal,
            'notes' => null,
        ];
    }
}
