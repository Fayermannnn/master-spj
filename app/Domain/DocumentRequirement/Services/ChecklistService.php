<?php

declare(strict_types=1);

namespace App\Domain\DocumentRequirement\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Domain\DocumentRequirement\Enums\ChecklistStatus;
use App\Models\DocumentRequirement;
use App\Models\Project;
use App\Models\ProjectChecklistItem;
use Illuminate\Support\Collection;

/**
 * @domain DocumentRequirement
 *
 * Menentukan requirement mana yang berlaku untuk sebuah project (base
 * filter project_type + RequirementRuleEvaluator), dan memastikan baris
 * checklist ada untuk masing-masing (idempotent — tidak menghapus baris
 * yang statusnya sudah diisi meski requirement belakangan tidak lagi
 * cocok, supaya histori tidak hilang diam-diam).
 */
class ChecklistService
{
    public function __construct(
        private readonly RequirementRuleEvaluator $evaluator,
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * @return Collection<int, ProjectChecklistItem>
     */
    public function sync(Project $project): Collection
    {
        $applicableRequirements = $this->applicableRequirements($project);

        $existingRequirementIds = $project->checklistItems()->pluck('document_requirement_id');

        foreach ($applicableRequirements as $requirement) {
            if ($existingRequirementIds->doesntContain($requirement->id)) {
                $project->checklistItems()->create([
                    'document_requirement_id' => $requirement->id,
                    'status' => ChecklistStatus::Missing->value,
                ]);
            }
        }

        $sortOrders = DocumentRequirement::query()->pluck('sort_order', 'id');

        return $project->checklistItems()
            ->with('documentRequirement')
            ->get()
            ->sortBy(fn (ProjectChecklistItem $item) => $sortOrders[$item->document_requirement_id] ?? 0);
    }

    /**
     * @return Collection<int, DocumentRequirement>
     */
    public function applicableRequirements(Project $project): Collection
    {
        return DocumentRequirement::query()
            ->where('is_active', true)
            ->where(function ($query) use ($project): void {
                $query->whereNull('project_type_id')
                    ->orWhere('project_type_id', $project->project_type_id);
            })
            ->with('rules')
            ->get()
            ->filter(fn (DocumentRequirement $requirement) => $this->evaluator->passes($project, $requirement->rules))
            ->values();
    }

    public function updateStatus(ProjectChecklistItem $item, ChecklistStatus $status, ?string $notes = null): ProjectChecklistItem
    {
        $before = $item->getAttributes();

        $item->update(['status' => $status->value, 'notes' => $notes]);

        $this->auditLog->record('DocumentRequirement', 'checklist_updated', $item, before: $before, after: $item->getChanges());

        return $item;
    }
}
