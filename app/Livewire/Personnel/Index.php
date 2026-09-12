<?php

declare(strict_types=1);

namespace App\Livewire\Personnel;

use App\Domain\Personnel\Services\PersonnelService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Personel'])]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Personnel::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(string $personnelId, PersonnelService $service): void
    {
        $personnel = Personnel::query()->findOrFail($personnelId);
        $this->authorize('delete', $personnel);

        try {
            $service->delete($personnel);
            session()->flash('status', "Personel \"{$personnel->name}\" berhasil dihapus.");
        } catch (DomainActionException $exception) {
            $this->addError('delete', $exception->getMessage());
        }
    }

    /**
     * @return LengthAwarePaginator<int, Personnel>
     */
    public function personnel(): LengthAwarePaginator
    {
        /** @var User $viewer */
        $viewer = Auth::user();

        return Personnel::query()
            ->with('category')
            ->when(! $viewer->hasRole('super_admin'), fn ($query) => $query->where('organization_id', $viewer->organization_id))
            ->when($this->search !== '', fn ($query) => $query->where('name', 'ilike', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(10);
    }

    public function render(): View
    {
        return view('livewire.personnel.index', [
            'personnel' => $this->personnel(),
        ]);
    }
}
