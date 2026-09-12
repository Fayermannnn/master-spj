<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CostItem;
use App\Models\Payment;
use App\Models\PaymentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentItem>
 */
class PaymentItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'cost_item_id' => CostItem::factory(),
            'amount' => fake()->randomFloat(2, 100_000, 10_000_000),
            'notes' => null,
        ];
    }
}
