<?php

declare(strict_types=1);

namespace App\Domain\Spj\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Domain\DocumentRequirement\Services\ChecklistService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Domain\Spj\Enums\SpjPackageStatus;
use App\Models\Document;
use App\Models\DocumentRequirement;
use App\Models\Evidence;
use App\Models\Payment;
use App\Models\Project;
use App\Models\SpjItem;
use App\Models\SpjPackage;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * @domain Spj
 *
 * Mengelola siklus SpjPackage (Draft -> Finalized, tidak ada jalan
 * balik — lihat SpjPackageStatus) dan manifest-nya (SpjItem, menunjuk
 * satu Document ATAU satu Evidence). "Kelengkapan" dihitung dinamis
 * terhadap `ChecklistService::applicableRequirements()` — bukan status
 * tersimpan — supaya seragam untuk paket per-termin maupun paket level
 * project (lihat PROJECT_DECISIONS.md D-019).
 */
class SpjPackageService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly ChecklistService $checklistService,
    ) {}

    public function create(Project $project, string $name, ?Payment $payment, ?string $notes, ?User $creator): SpjPackage
    {
        $package = $project->spjPackages()->create([
            'payment_id' => $payment?->id,
            'name' => $name,
            'status' => SpjPackageStatus::Draft->value,
            'notes' => $notes,
            'created_by' => $creator?->id,
        ]);

        $this->auditLog->record('Spj', 'package_created', $package, after: [
            'project_id' => $project->id,
            'payment_id' => $payment?->id,
            'name' => $name,
        ]);

        return $package;
    }

    public function addDocument(SpjPackage $package, Document $document): SpjItem
    {
        $this->assertDraft($package);

        if ($package->items()->where('document_id', $document->id)->exists()) {
            throw new DomainActionException('Dokumen ini sudah ada dalam paket.');
        }

        $item = $package->items()->create([
            'document_id' => $document->id,
            'sort_order' => $this->nextSortOrder($package),
        ]);

        $this->auditLog->record('Spj', 'item_added', $package, after: ['document_id' => $document->id]);

        return $item;
    }

    public function addEvidence(SpjPackage $package, Evidence $evidence): SpjItem
    {
        $this->assertDraft($package);

        if ($package->items()->where('evidence_id', $evidence->id)->exists()) {
            throw new DomainActionException('Bukti ini sudah ada dalam paket.');
        }

        $item = $package->items()->create([
            'evidence_id' => $evidence->id,
            'sort_order' => $this->nextSortOrder($package),
        ]);

        $this->auditLog->record('Spj', 'item_added', $package, after: ['evidence_id' => $evidence->id]);

        return $item;
    }

    public function removeItem(SpjItem $item): void
    {
        $package = $item->spjPackage;

        if ($package === null) {
            throw new DomainActionException('Item manifest tidak terhubung ke paket manapun.');
        }

        $this->assertDraft($package);

        $before = $item->getAttributes();

        $item->delete();

        $this->auditLog->record('Spj', 'item_removed', $package, before: $before);
    }

    public function finalize(SpjPackage $package): SpjPackage
    {
        $this->assertDraft($package);

        if ($package->items()->count() === 0) {
            throw new DomainActionException('Paket tidak boleh kosong saat difinalisasi.');
        }

        $package->update(['status' => SpjPackageStatus::Finalized->value, 'finalized_at' => now()]);

        $this->auditLog->record('Spj', 'package_finalized', $package);

        return $package;
    }

    public function delete(SpjPackage $package): void
    {
        $before = $package->getAttributes();

        $package->delete();

        $this->auditLog->record('Spj', 'package_deleted', $package, before: $before);
    }

    /**
     * @return array{total: int, covered: int, percentage: int, rows: Collection<int, array{requirement: DocumentRequirement, covered: bool}>}
     */
    public function coverage(SpjPackage $package): array
    {
        $project = $package->project;

        if ($project === null) {
            throw new DomainActionException('Paket tidak terhubung ke project manapun.');
        }

        $applicable = $this->checklistService->applicableRequirements($project);

        /** @var Collection<int, string> $coveredRequirementIds */
        $coveredRequirementIds = $package->items()->with(['document', 'evidence'])->get()
            ->map(fn (SpjItem $item): ?string => $item->documentRequirementId())
            ->filter()
            ->unique();

        $rows = $applicable->map(fn ($requirement): array => [
            'requirement' => $requirement,
            'covered' => $coveredRequirementIds->contains($requirement->id),
        ]);

        $total = $rows->count();
        $covered = $rows->where('covered', true)->count();

        return [
            'total' => $total,
            'covered' => $covered,
            'percentage' => $total > 0 ? (int) round(($covered / $total) * 100) : 0,
            'rows' => $rows,
        ];
    }

    private function assertDraft(SpjPackage $package): void
    {
        if ($package->status !== SpjPackageStatus::Draft) {
            throw new DomainActionException('Paket yang sudah berstatus Final tidak dapat diubah lagi. Buat paket baru untuk revisi.');
        }
    }

    private function nextSortOrder(SpjPackage $package): int
    {
        return ((int) $package->items()->max('sort_order')) + 1;
    }
}
