<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentRequirement;
use App\Models\DocumentTemplate;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'document_requirement_id' => DocumentRequirement::factory(),
            'document_template_id' => DocumentTemplate::factory(),
            'payment_id' => null,
            'version' => 1,
            'name' => fake()->words(3, true),
            'data_snapshot' => [],
            'disk' => 'local',
            'path' => 'generated-documents/'.fake()->uuid().'.docx',
            'original_filename' => 'dokumen.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'size' => fake()->numberBetween(10_000, 500_000),
            'pdf_disk' => null,
            'pdf_path' => null,
            'pdf_original_filename' => null,
            'pdf_size' => null,
            'generated_by' => null,
            'generated_at' => now(),
            'notes' => null,
        ];
    }
}
