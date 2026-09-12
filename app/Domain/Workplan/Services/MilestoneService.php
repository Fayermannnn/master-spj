<?php

declare(strict_types=1);

namespace App\Domain\Workplan\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\User;

/**
 * @domain Workplan
 */
class MilestoneService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Project $project, array $data, ?User $creator): Milestone
    {
        $milestone = $project->milestones()->create([
            ...$data,
            'created_by' => $creator?->id,
        ]);

        $this->auditLog->record('Workplan', 'milestone_created', $milestone, after: $milestone->getAttributes());

        return $milestone;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Milestone $milestone, array $data): Milestone
    {
        $before = $milestone->getAttributes();

        $milestone->update($data);

        $this->auditLog->record('Workplan', 'milestone_updated', $milestone, before: $before, after: $milestone->getChanges());

        return $milestone;
    }

    public function delete(Milestone $milestone): void
    {
        $before = $milestone->getAttributes();

        $milestone->delete();

        $this->auditLog->record('Workplan', 'milestone_deleted', $milestone, before: $before);
    }
}
