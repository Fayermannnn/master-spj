<?php

declare(strict_types=1);

namespace App\Domain\DocumentTemplate\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Models\TemplateVariable;

/**
 * @domain DocumentTemplate
 *
 * Tidak ada foreign key ke tabel ini (detected_variables pada
 * DocumentTemplate hanya snapshot JSON hasil scan, bukan relasi) —
 * sehingga tidak perlu delete-guard seperti master data lain.
 */
class TemplateVariableService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TemplateVariable
    {
        $variable = TemplateVariable::query()->create($data);

        $this->auditLog->record('DocumentTemplate', 'variable_created', $variable, after: $variable->getAttributes());

        return $variable;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TemplateVariable $variable, array $data): TemplateVariable
    {
        $before = $variable->getAttributes();

        $variable->update($data);

        $this->auditLog->record('DocumentTemplate', 'variable_updated', $variable, before: $before, after: $variable->getChanges());

        return $variable;
    }

    public function delete(TemplateVariable $variable): void
    {
        $before = $variable->getAttributes();

        $variable->delete();

        $this->auditLog->record('DocumentTemplate', 'variable_deleted', $variable, before: $before);
    }
}
