<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'termin_number' => fake()->unique()->numberBetween(1, 1000),
            'name' => 'Termin 1',
            'percentage' => 30,
            'amount' => fake()->randomFloat(2, 50_000_000, 500_000_000),
            'target_date' => fake()->dateTimeBetween('now', '+3 months'),
            'trigger' => 'Penyerahan Laporan Pendahuluan',
            'required_items' => null,
            'status' => PaymentStatus::Pending->value,
            'submission_date' => null,
            'approval_date' => null,
            'payment_date' => null,
            'notes' => null,
        ];
    }
}
