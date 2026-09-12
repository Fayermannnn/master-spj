<?php

declare(strict_types=1);

namespace App\Domain\Contract\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\Contract;
use App\Models\ContractAddendum;
use App\Models\User;

/**
 * @domain Contract
 *
 * Mencatat riwayat adendum kontrak DAN menerapkan nilai baru ke
 * `Contract.contract_value` sekaligus (kalau adendum mengubah nilai) —
 * `Contract` tetap mencerminkan nilai EFEKTIF saat ini, `ContractAddendum`
 * menyimpan jejak kenapa/bagaimana nilai itu berubah (PROJECT_DECISIONS.md
 * D-026).
 */
class ContractAddendumService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Contract $contract, array $data, ?User $creator): ContractAddendum
    {
        $newValue = $data['new_value'] !== null && $data['new_value'] !== ''
            ? (float) $data['new_value']
            : null;

        $addendum = $contract->addenda()->create([
            'addendum_number' => $data['addendum_number'] ?? null,
            'addendum_date' => $data['addendum_date'],
            'reason' => $data['reason'],
            'previous_value' => $contract->contract_value,
            'new_value' => $newValue,
            'created_by' => $creator?->id,
        ]);

        if ($newValue !== null) {
            $contract->update(['contract_value' => $newValue]);
        }

        $this->auditLog->record('Contract', 'addendum_created', $addendum, after: $addendum->getAttributes());

        return $addendum;
    }

    /**
     * Hanya adendum TERAKHIR (berdasar urutan dibuat) yang boleh dihapus
     * — supaya urutan riwayat nilai kontrak tidak berlubang di tengah.
     * Menghapusnya mengembalikan `contract_value` ke snapshot
     * `previous_value` milik adendum itu.
     */
    public function delete(ContractAddendum $addendum): void
    {
        $contract = $addendum->contract;

        if ($contract === null) {
            throw new DomainActionException('Adendum tidak terhubung ke kontrak manapun.');
        }

        // Diurutkan lewat ULID (bukan created_at) — ULID sortable secara
        // alami dan tidak bentrok saat dua adendum dibuat pada detik yang
        // sama (mis. dalam test), beda dari timestamp yang presisinya
        // cuma sedetik.
        $latest = $contract->addenda()->orderByDesc('id')->first();

        if ($latest === null || $latest->id !== $addendum->id) {
            throw new DomainActionException('Hanya adendum TERAKHIR yang dapat dihapus, agar urutan riwayat nilai kontrak tetap konsisten.');
        }

        $before = $addendum->getAttributes();

        if ($addendum->new_value !== null) {
            $contract->update(['contract_value' => $addendum->previous_value]);
        }

        $addendum->delete();

        $this->auditLog->record('Contract', 'addendum_deleted', $contract, before: $before);
    }
}
