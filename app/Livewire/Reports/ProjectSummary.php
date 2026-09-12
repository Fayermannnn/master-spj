<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Domain\ProjectManagement\Enums\ProjectStatus;
use App\Domain\Reporting\Services\ProjectSummaryReportService;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Laporan Ringkasan Project'])]
class ProjectSummary extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $clientId = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Project::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingClientId(): void
    {
        $this->resetPage();
    }

    /**
     * @return array{organization_id: ?string, search: string, status: string, client_id: string}
     */
    public function filters(): array
    {
        /** @var User $viewer */
        $viewer = Auth::user();

        return [
            'organization_id' => $viewer->hasRole('super_admin') ? null : $viewer->organization_id,
            'search' => $this->search,
            'status' => $this->status,
            'client_id' => $this->clientId,
        ];
    }

    /**
     * Tabel di layar DIPAGINASI (beda dari `rows()` yang dipakai ekspor
     * Excel/PDF, yang selalu mengambil SEMUA baris yang cocok filter —
     * lihat PROJECT_DECISIONS.md D-021) supaya jumlah project yang
     * tumbuh besar tidak membuat satu render menghitung
     * `ChecklistService::sync()` untuk semuanya sekaligus, hanya untuk
     * project pada halaman yang sedang dilihat.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginatedRows(ProjectSummaryReportService $service): LengthAwarePaginator
    {
        return $service->query($this->filters())->paginate(15)->through($service->toRow(...));
    }

    public function render(ProjectSummaryReportService $service): View
    {
        /** @var User $viewer */
        $viewer = Auth::user();

        return view('livewire.reports.project-summary', [
            'rows' => $this->paginatedRows($service),
            'totals' => $service->aggregates($this->filters()),
            'statuses' => ProjectStatus::cases(),
            'clients' => Client::query()
                ->when(! $viewer->hasRole('super_admin'), fn (Builder $query) => $query->where('organization_id', $viewer->organization_id))
                ->orderBy('name')
                ->get(),
        ]);
    }
}
