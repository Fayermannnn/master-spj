<?php

declare(strict_types=1);

namespace App\Livewire\Organizations;

use App\Domain\Organization\Services\OrganizationService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\Organization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Organisasi'])]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Organization::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(string $organizationId, OrganizationService $service): void
    {
        $organization = Organization::query()->findOrFail($organizationId);
        $this->authorize('delete', $organization);

        try {
            $service->delete($organization);
            session()->flash('status', "Organisasi \"{$organization->name}\" berhasil dihapus.");
        } catch (DomainActionException $exception) {
            $this->addError('delete', $exception->getMessage());
        }
    }

    /**
     * @return LengthAwarePaginator<int, Organization>
     */
    public function organizations(): LengthAwarePaginator
    {
        return Organization::query()
            ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'ilike', "%{$this->search}%")
                    ->orWhere('code', 'ilike', "%{$this->search}%");
            }))
            ->orderBy('name')
            ->paginate(10);
    }

    public function render(): View
    {
        return view('livewire.organizations.index', [
            'organizations' => $this->organizations(),
        ]);
    }
}
