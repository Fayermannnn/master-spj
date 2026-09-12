<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NumberingSetting;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NumberingSetting>
 */
class NumberingSettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'format_template' => '{seq}/SPJ/{org}/{month_roman}/{year}',
            'next_sequence' => 1,
            'reset_period' => 'yearly',
            'last_reset_period_key' => null,
        ];
    }
}
