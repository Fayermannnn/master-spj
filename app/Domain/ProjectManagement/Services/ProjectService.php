<?php

declare(strict_types=1);

namespace App\Domain\ProjectManagement\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Domain\ProjectManagement\Enums\ProjectStatus;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\Project;

/**
 * @domain ProjectManagement
 */
class ProjectService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Project
    {
        $data['status'] ??= ProjectStatus::Draft->value;

        $project = Project::query()->create($data);

        $this->auditLog->record('ProjectManagement', 'created', $project, after: $project->getAttributes());

        return $project;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Project $project, array $data): Project
    {
        $before = $project->getAttributes();

        $project->update($data);

        $this->auditLog->record('ProjectManagement', 'updated', $project, before: $before, after: $project->getChanges());

        return $project;
    }

    public function delete(Project $project): void
    {
        if ($project->status !== ProjectStatus::Draft) {
            throw new DomainActionException(
                "Project \"{$project->name}\" tidak dapat dihapus karena statusnya sudah \"{$project->status->label()}\". Gunakan transisi status (mis. Arsipkan) alih-alih menghapus."
            );
        }

        $before = $project->getAttributes();

        $project->delete();

        $this->auditLog->record('ProjectManagement', 'deleted', $project, before: $before);
    }

    public function transitionStatus(Project $project, ProjectStatus $target): Project
    {
        if (! $project->status->canTransitionTo($target)) {
            throw new DomainActionException(
                "Status project tidak dapat diubah dari \"{$project->status->label()}\" ke \"{$target->label()}\"."
            );
        }

        $before = $project->status->value;

        $project->update(['status' => $target->value]);

        $this->auditLog->record('ProjectManagement', 'status_transitioned', $project, before: ['status' => $before], after: ['status' => $target->value]);

        return $project;
    }
}
