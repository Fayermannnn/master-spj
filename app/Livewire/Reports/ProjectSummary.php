<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Domain\ProjectManagement\Enums\ProjectStatus;
use App\Domain\Reporting\Services\ProjectSummaryReportService;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Laporan Ringkasan Project'])]
class ProjectSummary extends Component
{
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

    public function render(ProjectSummaryReportService $service): View
    {
        /** @var User $viewer */
        $viewer = Auth::user();

        return view('livewire.reports.project-summary', [
            'rows' => $service->rows($this->filters()),
            'statuses' => ProjectStatus::cases(),
            'clients' => Client::query()
                ->when(! $viewer->hasRole('super_admin'), fn (Builder $query) => $query->where('organization_id', $viewer->organization_id))
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{contract_value: float, total_paid: float}
     */
    public function totals(Collection $rows): array
    {
        return [
            'contract_value' => $rows->sum('contract_value'),
            'total_paid' => $rows->sum('total_paid'),
        ];
    }
}
