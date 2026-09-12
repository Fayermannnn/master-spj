<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $contractValue = fake()->randomFloat(2, 100_000_000, 5_000_000_000);

        return [
            'project_id' => Project::factory(),
            'contract_number' => fake()->unique()->numerify('027/KTR/####/2026'),
            'contract_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'spmk_number' => fake()->numerify('028/SPMK/####/2026'),
            'spmk_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'contract_value' => $contractValue,
            'tax_amount' => round($contractValue * 0.11, 2),
            'net_value' => round($contractValue * 0.89, 2),
            'notes' => null,
        ];
    }
}
