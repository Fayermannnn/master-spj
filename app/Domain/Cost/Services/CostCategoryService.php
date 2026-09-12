<?php

declare(strict_types=1);

namespace App\Domain\Cost\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\CostCategory;

/**
 * @domain Cost
 */
class CostCategoryService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): CostCategory
    {
        $category = CostCategory::query()->create($data);

        $this->auditLog->record('Cost', 'created', $category, after: $category->getAttributes());

        return $category;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CostCategory $category, array $data): CostCategory
    {
        $before = $category->getAttributes();

        $category->update($data);

        $this->auditLog->record('Cost', 'updated', $category, before: $before, after: $category->getChanges());

        return $category;
    }

    public function delete(CostCategory $category): void
    {
        if ($category->costItems()->exists()) {
            throw new DomainActionException(
                "Kategori biaya \"{$category->name}\" tidak dapat dihapus karena masih dipakai oleh satu atau lebih item biaya."
            );
        }

        $before = $category->getAttributes();

        $category->delete();

        $this->auditLog->record('Cost', 'deleted', $category, before: $before);
    }
}
