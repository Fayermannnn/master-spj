<?php

declare(strict_types=1);

namespace App\Domain\ProjectManagement\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\ProjectType;

/**
 * @domain ProjectManagement
 */
class ProjectTypeService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ProjectType
    {
        $projectType = ProjectType::query()->create($data);

        $this->auditLog->record('ProjectManagement', 'created', $projectType, after: $projectType->getAttributes());

        return $projectType;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProjectType $projectType, array $data): ProjectType
    {
        $before = $projectType->getAttributes();

        $projectType->update($data);

        $this->auditLog->record('ProjectManagement', 'updated', $projectType, before: $before, after: $projectType->getChanges());

        return $projectType;
    }

    public function delete(ProjectType $projectType): void
    {
        if ($projectType->projects()->exists()) {
            throw new DomainActionException(
                "Jenis project \"{$projectType->name}\" tidak dapat dihapus karena masih dipakai oleh satu atau lebih project."
            );
        }

        $before = $projectType->getAttributes();

        $projectType->delete();

        $this->auditLog->record('ProjectManagement', 'deleted', $projectType, before: $before);
    }
}
