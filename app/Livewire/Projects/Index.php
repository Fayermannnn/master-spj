<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Domain\ProjectManagement\Services\ProjectService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Project'])]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Project::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(string $projectId, ProjectService $service): void
    {
        $project = Project::query()->findOrFail($projectId);
        $this->authorize('delete', $project);

        try {
            $service->delete($project);
            session()->flash('status', "Project \"{$project->name}\" berhasil dihapus.");
        } catch (DomainActionException $exception) {
            $this->addError('delete', $exception->getMessage());
        }
    }

    /**
     * @return LengthAwarePaginator<int, Project>
     */
    public function projects(): LengthAwarePaginator
    {
        /** @var User $viewer */
        $viewer = Auth::user();

        return Project::query()
            ->with(['projectType', 'client'])
            ->when(! $viewer->hasRole('super_admin'), fn ($query) => $query->where('organization_id', $viewer->organization_id))
            ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'ilike', "%{$this->search}%")
                    ->orWhere('code', 'ilike', "%{$this->search}%");
            }))
            ->latest()
            ->paginate(10);
    }

    public function render(): View
    {
        return view('livewire.projects.index', [
            'projects' => $this->projects(),
        ]);
    }
}
