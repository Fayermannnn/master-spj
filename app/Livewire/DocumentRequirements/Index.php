<?php

declare(strict_types=1);

namespace App\Livewire\DocumentRequirements;

use App\Domain\DocumentRequirement\Services\DocumentRequirementService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\DocumentRequirement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Kebutuhan Dokumen'])]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', DocumentRequirement::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(string $requirementId, DocumentRequirementService $service): void
    {
        $requirement = DocumentRequirement::query()->findOrFail($requirementId);
        $this->authorize('delete', $requirement);

        try {
            $service->delete($requirement);
            session()->flash('status', "Kebutuhan dokumen \"{$requirement->name}\" berhasil dihapus.");
        } catch (DomainActionException $exception) {
            $this->addError('delete', $exception->getMessage());
        }
    }

    /**
     * @return LengthAwarePaginator<int, DocumentRequirement>
     */
    public function requirements(): LengthAwarePaginator
    {
        return DocumentRequirement::query()
            ->with(['projectType', 'rules'])
            ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'ilike', "%{$this->search}%")
                    ->orWhere('code', 'ilike', "%{$this->search}%");
            }))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15);
    }

    public function render(): View
    {
        return view('livewire.document-requirements.index', [
            'requirements' => $this->requirements(),
        ]);
    }
}
