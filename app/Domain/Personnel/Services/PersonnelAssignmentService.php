<?php

declare(strict_types=1);

namespace App\Domain\Personnel\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Models\PersonnelAssignment;
use App\Models\Project;

/**
 * @domain Personnel
 */
class PersonnelAssignmentService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Project $project, array $data): PersonnelAssignment
    {
        $data['subtotal'] = round(((float) $data['quantity']) * ((float) $data['unit_price']), 2);

        $assignment = $project->personnelAssignments()->create($data);

        $this->auditLog->record('Personnel', 'assignment_created', $assignment, after: $assignment->getAttributes());

        return $assignment;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PersonnelAssignment $assignment, array $data): PersonnelAssignment
    {
        $before = $assignment->getAttributes();

        $data['subtotal'] = round(((float) $data['quantity']) * ((float) $data['unit_price']), 2);

        $assignment->update($data);

        $this->auditLog->record('Personnel', 'assignment_updated', $assignment, before: $before, after: $assignment->getChanges());

        return $assignment;
    }

    public function delete(PersonnelAssignment $assignment): void
    {
        $before = $assignment->getAttributes();

        $assignment->delete();

        $this->auditLog->record('Personnel', 'assignment_deleted', $assignment, before: $before);
    }
}
