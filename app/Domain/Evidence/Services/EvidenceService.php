<?php

declare(strict_types=1);

namespace App\Domain\Evidence\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Domain\Shared\Services\FileStorageService;
use App\Models\Evidence;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * @domain Evidence
 */
class EvidenceService
{
    public function __construct(
        private readonly FileStorageService $fileStorage,
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * @param  array{payment_id?: ?string, personnel_id?: ?string, document_requirement_id?: ?string, category?: ?string, description?: ?string, notes?: ?string}  $links
     */
    public function upload(Project $project, UploadedFile $file, string $name, array $links, ?User $uploader): Evidence
    {
        $stored = $this->fileStorage->store($file, "evidences/{$project->id}");

        $evidence = $project->evidences()->create([
            'payment_id' => $links['payment_id'] ?? null,
            'personnel_id' => $links['personnel_id'] ?? null,
            'document_requirement_id' => $links['document_requirement_id'] ?? null,
            'name' => $name,
            'category' => $links['category'] ?? null,
            'description' => $links['description'] ?? null,
            'disk' => $stored['disk'],
            'path' => $stored['path'],
            'original_filename' => $stored['original_filename'],
            'mime_type' => $stored['mime_type'],
            'size' => $stored['size'],
            'uploaded_by' => $uploader?->id,
            'notes' => $links['notes'] ?? null,
        ]);

        $this->auditLog->record('Evidence', 'uploaded', $evidence, after: [
            'project_id' => $project->id,
            'name' => $name,
            'original_filename' => $stored['original_filename'],
        ]);

        return $evidence;
    }

    public function delete(Evidence $evidence): void
    {
        $this->fileStorage->delete($evidence->disk, $evidence->path);

        $before = $evidence->getAttributes();

        $evidence->delete();

        $this->auditLog->record('Evidence', 'deleted', $evidence, before: $before);
    }
}
