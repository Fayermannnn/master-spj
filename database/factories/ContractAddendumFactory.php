<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contract;
use App\Models\ContractAddendum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractAddendum>
 */
class ContractAddendumFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'addendum_number' => fake()->numerify('001/ADD/####/2026'),
            'addendum_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'reason' => fake()->sentence(8),
            'previous_value' => 100_000_000,
            'new_value' => 120_000_000,
            'created_by' => null,
        ];
    }
}
