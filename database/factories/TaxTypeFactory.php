<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TaxType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TaxType>
 */
class TaxTypeFactory extends Factory
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
            'rate' => fake()->randomFloat(2, 0, 15),
            'is_active' => true,
            'description' => null,
        ];
    }
}
