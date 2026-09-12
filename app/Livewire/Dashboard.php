<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Domain\ProjectManagement\Enums\ProjectStatus;
use App\Domain\Workplan\Enums\WorkplanStatus;
use App\Models\Contract;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Dashboard'])]
class Dashboard extends Component
{
    public function render(): View
    {
        /** @var User $viewer */
        $viewer = Auth::user();
        $isSuperAdmin = $viewer->hasRole('super_admin');

        $projectIds = Project::query()
            ->when(! $isSuperAdmin, fn (Builder $query) => $query->where('organization_id', $viewer->organization_id))
            ->pluck('id');

        $projectsByStatus = Project::query()
            ->whereIn('id', $projectIds)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalContractValue = Contract::query()
            ->whereIn('project_id', $projectIds)
            ->sum('contract_value');

        $upcomingMilestones = Milestone::query()
            ->whereIn('project_id', $projectIds)
            ->where('status', '!=', WorkplanStatus::Completed->value)
            ->orderBy('target_date')
            ->with('project')
            ->limit(5)
            ->get();

        return view('livewire.dashboard', [
            'totalProjects' => $projectIds->count(),
            'activeProjects' => (int) ($projectsByStatus[ProjectStatus::Active->value] ?? 0),
            'projectsByStatus' => $projectsByStatus,
            'statuses' => ProjectStatus::cases(),
            'totalContractValue' => (float) $totalContractValue,
            'upcomingMilestones' => $upcomingMilestones,
        ]);
    }
}
