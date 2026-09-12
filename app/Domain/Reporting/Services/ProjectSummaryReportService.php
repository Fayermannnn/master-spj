<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Services;

use App\Domain\DocumentRequirement\Enums\ChecklistStatus;
use App\Domain\DocumentRequirement\Services\ChecklistService;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * @domain Reporting
 *
 * Membangun baris "Laporan Ringkasan Project" — satu sumber kebenaran
 * dipakai baik oleh tampilan layar (Livewire) maupun ekspor Excel/PDF,
 * supaya filter dan angka yang ditampilkan selalu konsisten dengan yang
 * diunduh.
 */
class ProjectSummaryReportService
{
    public function __construct(private readonly ChecklistService $checklistService) {}

    /**
     * @param  array{organization_id: ?string, search: string, status: string, client_id: string}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(array $filters): Collection
    {
        return $this->query($filters)->get()->map($this->toRow(...));
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow(Project $project): array
    {
        $checklistItems = $this->checklistService->sync($project);
        $total = $checklistItems->count();
        $fulfilled = $checklistItems->where('status', ChecklistStatus::Fulfilled)->count();
        $client = $project->client;

        return [
            'code' => $project->code,
            'name' => $project->name,
            'client' => $client !== null ? $client->name : '—',
            'status' => $project->status->label(),
            'contract_value' => $project->contract?->contract_value,
            'total_payment' => (float) $project->payments->sum('amount'),
            'total_paid' => (float) $project->payments->where('status', PaymentStatus::Paid)->sum('amount'),
            'checklist_total' => $total,
            'checklist_fulfilled' => $fulfilled,
            'checklist_percentage' => $total > 0 ? (int) round(($fulfilled / $total) * 100) : 0,
        ];
    }

    /**
     * @param  array{organization_id: ?string, search: string, status: string, client_id: string}  $filters
     * @return Builder<Project>
     */
    public function query(array $filters): Builder
    {
        return Project::query()
            ->with(['client', 'contract', 'payments'])
            ->when(! empty($filters['organization_id']), fn (Builder $query) => $query->where('organization_id', $filters['organization_id']))
            ->when(! empty($filters['status']), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['client_id']), fn (Builder $query) => $query->where('client_id', $filters['client_id']))
            ->when(! empty($filters['search']), function (Builder $query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'ilike', "%{$search}%")->orWhere('code', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('name');
    }
}
