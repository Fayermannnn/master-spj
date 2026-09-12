<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Personnel\Enums\PersonnelDocumentType;
use App\Models\Personnel;
use App\Models\PersonnelDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonnelDocument>
 */
class PersonnelDocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'personnel_id' => Personnel::factory(),
            'document_type' => fake()->randomElement(PersonnelDocumentType::cases())->value,
            'disk' => 'local',
            'path' => 'personnel/'.fake()->uuid().'.pdf',
            'original_filename' => 'dokumen.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(10_000, 2_000_000),
            'uploaded_by' => null,
            'notes' => null,
        ];
    }
}
