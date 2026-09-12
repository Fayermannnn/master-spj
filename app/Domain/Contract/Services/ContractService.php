<?php

declare(strict_types=1);

namespace App\Domain\Contract\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Models\Contract;
use App\Models\Project;

/**
 * @domain Contract
 *
 * Relasi Contract-Project bersifat 1:1 — service ini melakukan upsert
 * (create kalau belum ada, update kalau sudah ada) alih-alih memisahkan
 * create()/update() seperti domain lain, karena dari sisi UI kontrak
 * selalu diedit lewat satu form yang sama pada halaman detail project.
 */
class ContractService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(Project $project, array $data): Contract
    {
        $existing = $project->contract;
        $before = $existing?->getAttributes();

        $contract = Contract::query()->updateOrCreate(['project_id' => $project->id], $data);

        $this->auditLog->record(
            'Contract',
            $existing ? 'updated' : 'created',
            $contract,
            before: $before ?? [],
            after: $existing ? $contract->getChanges() : $contract->getAttributes(),
        );

        return $contract;
    }
}
