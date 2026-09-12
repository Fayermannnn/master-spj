<?php

declare(strict_types=1);

namespace App\Livewire\ProjectTypes;

use App\Domain\ProjectManagement\Services\ProjectTypeService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\ProjectType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Jenis Project'])]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', ProjectType::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(string $projectTypeId, ProjectTypeService $service): void
    {
        $projectType = ProjectType::query()->findOrFail($projectTypeId);
        $this->authorize('delete', $projectType);

        try {
            $service->delete($projectType);
            session()->flash('status', "Jenis project \"{$projectType->name}\" berhasil dihapus.");
        } catch (DomainActionException $exception) {
            $this->addError('delete', $exception->getMessage());
        }
    }

    /**
     * @return LengthAwarePaginator<int, ProjectType>
     */
    public function projectTypes(): LengthAwarePaginator
    {
        return ProjectType::query()
            ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'ilike', "%{$this->search}%")
                    ->orWhere('code', 'ilike', "%{$this->search}%");
            }))
            ->orderBy('name')
            ->paginate(10);
    }

    public function render(): View
    {
        return view('livewire.project-types.index', [
            'projectTypes' => $this->projectTypes(),
        ]);
    }
}
