<?php

declare(strict_types=1);

namespace App\Domain\DocumentRequirement\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\DocumentRequirement;
use Illuminate\Support\Facades\DB;

/**
 * @domain DocumentRequirement
 */
class DocumentRequirementService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $rules
     */
    public function create(array $data, array $rules): DocumentRequirement
    {
        return DB::transaction(function () use ($data, $rules): DocumentRequirement {
            $requirement = DocumentRequirement::query()->create($data);
            $this->syncRules($requirement, $rules);

            $this->auditLog->record('DocumentRequirement', 'created', $requirement, after: $requirement->getAttributes());

            return $requirement;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $rules
     */
    public function update(DocumentRequirement $requirement, array $data, array $rules): DocumentRequirement
    {
        return DB::transaction(function () use ($requirement, $data, $rules): DocumentRequirement {
            $before = $requirement->getAttributes();

            $requirement->update($data);
            $this->syncRules($requirement, $rules);

            $this->auditLog->record('DocumentRequirement', 'updated', $requirement, before: $before, after: $requirement->getChanges());

            return $requirement;
        });
    }

    public function delete(DocumentRequirement $requirement): void
    {
        if ($requirement->checklistItems()->exists()) {
            throw new DomainActionException(
                "Kebutuhan dokumen \"{$requirement->name}\" tidak dapat dihapus karena sudah dipakai di checklist salah satu project."
            );
        }

        $before = $requirement->getAttributes();

        $requirement->delete();

        $this->auditLog->record('DocumentRequirement', 'deleted', $requirement, before: $before);
    }

    /**
     * @param  list<array<string, mixed>>  $rules
     */
    private function syncRules(DocumentRequirement $requirement, array $rules): void
    {
        $submittedIds = [];

        foreach ($rules as $rule) {
            $id = $rule['id'] ?? null;
            unset($rule['id']);

            if ($id) {
                $requirement->rules()->whereKey($id)->update($rule);
                $submittedIds[] = $id;
            } else {
                $created = $requirement->rules()->create($rule);
                $submittedIds[] = $created->id;
            }
        }

        $requirement->rules()->whereNotIn('id', $submittedIds)->delete();
    }
}
