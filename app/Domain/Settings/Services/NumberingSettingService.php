<?php

declare(strict_types=1);

namespace App\Domain\Settings\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Models\NumberingSetting;
use App\Models\Organization;

/**
 * @domain Settings
 *
 * Upsert `NumberingSetting` — relasi 1:1 dengan Organization, pola sama
 * `ContractService::save()` (D-008): create kalau belum ada, update
 * kalau sudah ada, satu form yang sama untuk keduanya.
 */
class NumberingSettingService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(Organization $organization, array $data): NumberingSetting
    {
        $existing = $organization->numberingSetting;
        $before = $existing?->getAttributes();

        $setting = NumberingSetting::query()->updateOrCreate(['organization_id' => $organization->id], $data);

        $this->auditLog->record(
            'Settings',
            $existing ? 'numbering_updated' : 'numbering_created',
            $setting,
            before: $before ?? [],
            after: $existing ? $setting->getChanges() : $setting->getAttributes(),
        );

        return $setting;
    }
}
