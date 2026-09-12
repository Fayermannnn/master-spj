<?php

declare(strict_types=1);

namespace App\Domain\Organization\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\Organization;

/**
 * @domain Organization
 */
class OrganizationService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Organization
    {
        $organization = Organization::query()->create($data);

        $this->auditLog->record('Organization', 'created', $organization, after: $organization->getAttributes());

        return $organization;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Organization $organization, array $data): Organization
    {
        $before = $organization->getAttributes();

        $organization->update($data);

        $this->auditLog->record('Organization', 'updated', $organization, before: $before, after: $organization->getChanges());

        return $organization;
    }

    public function delete(Organization $organization): void
    {
        if ($organization->users()->exists()) {
            throw new DomainActionException(
                "Organisasi \"{$organization->name}\" tidak dapat dihapus karena masih memiliki user aktif. Pindahkan atau nonaktifkan user tersebut terlebih dahulu."
            );
        }

        $before = $organization->getAttributes();

        $organization->delete();

        $this->auditLog->record('Organization', 'deleted', $organization, before: $before);
    }
}
