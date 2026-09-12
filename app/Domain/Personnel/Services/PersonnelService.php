<?php

declare(strict_types=1);

namespace App\Domain\Personnel\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\Personnel;

/**
 * @domain Personnel
 */
class PersonnelService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Personnel
    {
        $personnel = Personnel::query()->create($data);

        $this->auditLog->record('Personnel', 'created', $personnel, after: $personnel->getAttributes());

        return $personnel;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Personnel $personnel, array $data): Personnel
    {
        $before = $personnel->getAttributes();

        $personnel->update($data);

        $this->auditLog->record('Personnel', 'updated', $personnel, before: $before, after: $personnel->getChanges());

        return $personnel;
    }

    public function delete(Personnel $personnel): void
    {
        if ($personnel->assignments()->exists()) {
            throw new DomainActionException(
                "Personel \"{$personnel->name}\" tidak dapat dihapus karena masih memiliki penugasan project."
            );
        }

        $before = $personnel->getAttributes();

        $personnel->delete();

        $this->auditLog->record('Personnel', 'deleted', $personnel, before: $before);
    }
}
