<?php

declare(strict_types=1);

namespace App\Domain\Personnel\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Models\Project;
use App\Models\TravelAssignment;
use App\Models\User;

/**
 * @domain Personnel
 *
 * Mencatat perjalanan dinas seorang Personnel dalam rangka satu
 * Project — dasar pengisian otomatis Surat Perjalanan Dinas (SPPD,
 * PROJECT_DECISIONS.md D-034).
 */
class TravelAssignmentService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Project $project, array $data, ?User $creator): TravelAssignment
    {
        $travelAssignment = $project->travelAssignments()->create([
            ...$data,
            'created_by' => $creator?->id,
        ]);

        $this->auditLog->record('Personnel', 'travel_assignment_created', $travelAssignment, after: $travelAssignment->getAttributes());

        return $travelAssignment;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TravelAssignment $travelAssignment, array $data): TravelAssignment
    {
        $before = $travelAssignment->getAttributes();

        $travelAssignment->update($data);

        $this->auditLog->record('Personnel', 'travel_assignment_updated', $travelAssignment, before: $before, after: $travelAssignment->getChanges());

        return $travelAssignment;
    }

    public function delete(TravelAssignment $travelAssignment): void
    {
        $before = $travelAssignment->getAttributes();

        $travelAssignment->delete();

        $this->auditLog->record('Personnel', 'travel_assignment_deleted', $travelAssignment, before: $before);
    }
}
