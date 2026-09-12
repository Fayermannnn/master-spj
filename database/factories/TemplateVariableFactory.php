<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\DocumentTemplate\Enums\TemplateVariableDataType;
use App\Models\TemplateVariable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemplateVariable>
 */
class TemplateVariableFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = fake()->unique()->word().'.'.fake()->word();

        return [
            'key' => $key,
            'label' => ucfirst(str_replace('.', ' ', $key)),
            'data_type' => TemplateVariableDataType::Text->value,
            'description' => null,
            'is_active' => true,
        ];
    }
}
