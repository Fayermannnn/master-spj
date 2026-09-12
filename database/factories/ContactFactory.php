<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Client\Enums\ContactType;
use App\Models\Client;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'type' => fake()->randomElement(ContactType::cases())->value,
            'name' => fake()->name(),
            'position' => fake()->jobTitle(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'notes' => null,
        ];
    }
}
