<?php

declare(strict_types=1);

namespace App\Livewire\TaxTypes;

use App\Domain\Cost\Services\TaxTypeService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\TaxType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Jenis Pajak'])]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', TaxType::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(string $taxTypeId, TaxTypeService $service): void
    {
        $taxType = TaxType::query()->findOrFail($taxTypeId);
        $this->authorize('delete', $taxType);

        try {
            $service->delete($taxType);
            session()->flash('status', "Jenis pajak \"{$taxType->name}\" berhasil dihapus.");
        } catch (DomainActionException $exception) {
            $this->addError('delete', $exception->getMessage());
        }
    }

    /**
     * @return LengthAwarePaginator<int, TaxType>
     */
    public function taxTypes(): LengthAwarePaginator
    {
        return TaxType::query()
            ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'ilike', "%{$this->search}%")
                    ->orWhere('code', 'ilike', "%{$this->search}%");
            }))
            ->orderBy('name')
            ->paginate(10);
    }

    public function render(): View
    {
        return view('livewire.tax-types.index', [
            'taxTypes' => $this->taxTypes(),
        ]);
    }
}
