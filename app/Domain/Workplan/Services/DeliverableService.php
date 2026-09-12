<?php

declare(strict_types=1);

namespace App\Domain\Workplan\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Models\Deliverable;
use App\Models\Project;
use App\Models\User;

/**
 * @domain Workplan
 */
class DeliverableService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Project $project, array $data, ?User $creator): Deliverable
    {
        $deliverable = $project->deliverables()->create([
            ...$data,
            'created_by' => $creator?->id,
        ]);

        $this->auditLog->record('Workplan', 'deliverable_created', $deliverable, after: $deliverable->getAttributes());

        return $deliverable;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Deliverable $deliverable, array $data): Deliverable
    {
        $before = $deliverable->getAttributes();

        $deliverable->update($data);

        $this->auditLog->record('Workplan', 'deliverable_updated', $deliverable, before: $before, after: $deliverable->getChanges());

        return $deliverable;
    }

    public function delete(Deliverable $deliverable): void
    {
        $before = $deliverable->getAttributes();

        $deliverable->delete();

        $this->auditLog->record('Workplan', 'deliverable_deleted', $deliverable, before: $before);
    }
}
