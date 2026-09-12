<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NotificationDismissal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationDismissal>
 */
class NotificationDismissalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'dismissal_key' => 'milestone:'.fake()->uuid(),
            'dismissed_at' => now(),
        ];
    }
}
