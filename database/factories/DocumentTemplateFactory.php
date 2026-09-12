<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\DocumentTemplate\Enums\TemplateStatus;
use App\Models\DocumentRequirement;
use App\Models\DocumentTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentTemplate>
 */
class DocumentTemplateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_requirement_id' => DocumentRequirement::factory(),
            'name' => fake()->words(3, true),
            'version' => 1,
            'status' => TemplateStatus::Draft->value,
            'disk' => 'local',
            'path' => 'document-templates/'.fake()->uuid().'.docx',
            'original_filename' => 'template.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'size' => fake()->numberBetween(10_000, 500_000),
            'detected_variables' => [],
            'description' => null,
            'uploaded_by' => null,
        ];
    }
}
