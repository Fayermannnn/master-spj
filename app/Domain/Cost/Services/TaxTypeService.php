<?php

declare(strict_types=1);

namespace App\Domain\Cost\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\TaxType;

/**
 * @domain Cost
 */
class TaxTypeService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TaxType
    {
        $taxType = TaxType::query()->create($data);

        $this->auditLog->record('Cost', 'created', $taxType, after: $taxType->getAttributes());

        return $taxType;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TaxType $taxType, array $data): TaxType
    {
        $before = $taxType->getAttributes();

        $taxType->update($data);

        $this->auditLog->record('Cost', 'updated', $taxType, before: $before, after: $taxType->getChanges());

        return $taxType;
    }

    public function delete(TaxType $taxType): void
    {
        if ($taxType->contracts()->exists() || $taxType->costItems()->exists()) {
            throw new DomainActionException(
                "Jenis pajak \"{$taxType->name}\" tidak dapat dihapus karena masih dipakai oleh kontrak atau item biaya."
            );
        }

        $before = $taxType->getAttributes();

        $taxType->delete();

        $this->auditLog->record('Cost', 'deleted', $taxType, before: $before);
    }
}
