<?php

declare(strict_types=1);

namespace App\Domain\Personnel\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\PersonnelCategory;

/**
 * @domain Personnel
 */
class PersonnelCategoryService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PersonnelCategory
    {
        $category = PersonnelCategory::query()->create($data);

        $this->auditLog->record('Personnel', 'created', $category, after: $category->getAttributes());

        return $category;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PersonnelCategory $category, array $data): PersonnelCategory
    {
        $before = $category->getAttributes();

        $category->update($data);

        $this->auditLog->record('Personnel', 'updated', $category, before: $before, after: $category->getChanges());

        return $category;
    }

    public function delete(PersonnelCategory $category): void
    {
        if ($category->personnel()->exists()) {
            throw new DomainActionException(
                "Kategori personel \"{$category->name}\" tidak dapat dihapus karena masih dipakai oleh satu atau lebih personel."
            );
        }

        $before = $category->getAttributes();

        $category->delete();

        $this->auditLog->record('Personnel', 'deleted', $category, before: $before);
    }
}
